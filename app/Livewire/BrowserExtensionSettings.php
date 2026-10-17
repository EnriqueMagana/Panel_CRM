<?php

namespace App\Livewire;

use App\Models\BrowserExtension;
use App\Models\BrowserExtensionConnection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use ZipArchive;

class BrowserExtensionSettings extends Component
{
    use WithFileUploads;

    public bool $connected = false;

    public ?string $browserName = null;

    public ?string $lastSeenAt = null;

    public string $search = '';

    public bool $formOpen = false;

    public string $extensionName = '';

    public string $extensionVersion = '';

    public string $extensionDescription = '';

    public string $extensionFeatures = '';

    public string $extensionBrowsers = 'Chrome, Edge';

    public bool $extensionActive = true;

    public $extensionZip = null;

    public function mount(): void
    {
        $this->syncConnectionState();
    }

    #[On('browser-extension-detected')]
    public function markConnected(string $browser = 'Chrome / Edge'): void
    {
        $connection = BrowserExtensionConnection::query()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'browser_name' => mb_substr($browser, 0, 80),
                'connected_at' => now(),
                'last_seen_at' => now(),
            ],
        );

        $this->connected = true;
        $this->browserName = $connection->browser_name;
        $this->lastSeenAt = $connection->last_seen_at?->diffForHumans();
        session()->flash('browser-extension-status', 'Extensión detectada y vinculada correctamente.');
    }

    public function disconnect(): void
    {
        $this->authorize('browser_extensions.manage');
        BrowserExtensionConnection::query()->where('user_id', Auth::id())->delete();
        $this->reset('connected', 'browserName', 'lastSeenAt');
        session()->flash('browser-extension-status', 'La extensión se desvinculó de tu cuenta.');
    }

    public function openExtensionForm(): void
    {
        $this->authorize('browser_extensions.manage');
        $this->resetExtensionForm();
        $this->formOpen = true;
    }

    public function cancelExtensionForm(): void
    {
        $this->formOpen = false;
        $this->resetExtensionForm();
    }

    public function saveExtension(): void
    {
        $this->authorize('browser_extensions.manage');
        $validated = $this->validate([
            'extensionName' => ['required', 'string', 'max:120'],
            'extensionVersion' => ['required', 'string', 'max:30', 'regex:/^[0-9A-Za-z][0-9A-Za-z._-]*$/'],
            'extensionDescription' => ['required', 'string', 'max:2000'],
            'extensionFeatures' => ['nullable', 'string', 'max:3000'],
            'extensionBrowsers' => ['required', 'string', 'max:160'],
            'extensionActive' => ['boolean'],
            'extensionZip' => ['required', 'file', 'mimes:zip', 'max:20480'],
        ], [
            'extensionVersion.regex' => 'Usa una versión como 1.0.0 o 2.1-beta.',
            'extensionZip.required' => 'Selecciona el archivo ZIP de la extensión.',
            'extensionZip.mimes' => 'El archivo debe ser un ZIP válido.',
            'extensionZip.max' => 'El ZIP no puede superar 20 MB.',
        ]);

        if (! $this->hasValidManifest($this->extensionZip->getRealPath())) {
            return;
        }

        $baseSlug = Str::slug($validated['extensionName']) ?: 'extension';
        $slug = $baseSlug;
        $suffix = 2;
        while (BrowserExtension::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $fileName = $slug.'-'.Str::slug($validated['extensionVersion']).'-'.Str::lower(Str::random(8)).'.zip';
        $path = $this->extensionZip->storeAs('browser-extensions', $fileName, 'public');

        if (! is_string($path)) {
            $this->addError('extensionZip', 'No se pudo guardar el archivo. Inténtalo nuevamente.');

            return;
        }

        try {
            BrowserExtension::query()->create([
                'uploaded_by' => Auth::id(),
                'name' => trim($validated['extensionName']),
                'slug' => $slug,
                'version' => trim($validated['extensionVersion']),
                'description' => trim($validated['extensionDescription']),
                'features' => collect(preg_split('/\R/u', $validated['extensionFeatures'] ?? ''))
                    ->map(fn (string $feature) => trim($feature))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                'supported_browsers' => trim($validated['extensionBrowsers']),
                'zip_path' => $path,
                'zip_size' => Storage::disk('public')->size($path),
                'is_active' => $validated['extensionActive'],
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            report($exception);
            $this->addError('extensionZip', 'No se pudo añadir la extensión al catálogo.');

            return;
        }

        $this->formOpen = false;
        $this->resetExtensionForm();
        session()->flash('browser-extension-status', 'Extensión añadida al catálogo.');
    }

    public function toggleExtension(int $extensionId): void
    {
        $this->authorize('browser_extensions.manage');
        $extension = BrowserExtension::query()->findOrFail($extensionId);
        $extension->update(['is_active' => ! $extension->is_active]);
        session()->flash('browser-extension-status', $extension->is_active ? 'Extensión publicada.' : 'Extensión desactivada.');
    }

    public function render()
    {
        $canManage = Auth::user()->can('browser_extensions.manage');
        $extensions = BrowserExtension::query()
            ->with('uploader:id,name')
            ->when(! $canManage, fn ($query) => $query->where('is_active', true))
            ->when(trim($this->search) !== '', function ($query): void {
                $term = '%'.trim($this->search).'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('supported_browsers', 'like', $term));
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('livewire.browser-extension-settings', compact('extensions', 'canManage'));
    }

    private function syncConnectionState(): void
    {
        $connection = BrowserExtensionConnection::query()->where('user_id', Auth::id())->first();
        $this->connected = $connection?->connected_at !== null;
        $this->browserName = $connection?->browser_name;
        $this->lastSeenAt = $connection?->last_seen_at?->diffForHumans();
    }

    private function resetExtensionForm(): void
    {
        $this->reset(
            'extensionName',
            'extensionVersion',
            'extensionDescription',
            'extensionFeatures',
            'extensionZip',
        );
        $this->extensionBrowsers = 'Chrome, Edge';
        $this->extensionActive = true;
        $this->resetValidation();
    }

    private function hasValidManifest(string $zipPath): bool
    {
        if (! class_exists(ZipArchive::class)) {
            $this->addError('extensionZip', 'El servidor no tiene habilitado el soporte para validar archivos ZIP.');

            return false;
        }

        $archive = new ZipArchive;
        if ($archive->open($zipPath) !== true) {
            $this->addError('extensionZip', 'No se pudo abrir el archivo ZIP.');

            return false;
        }

        $manifestIndex = $archive->locateName('manifest.json', ZipArchive::FL_NOCASE);
        $manifest = $manifestIndex === false ? false : $archive->getFromIndex($manifestIndex);
        $archive->close();
        $decoded = is_string($manifest) ? json_decode($manifest, true) : null;

        if (! is_array($decoded) || ! in_array($decoded['manifest_version'] ?? null, [2, 3], true)) {
            $this->addError('extensionZip', 'El ZIP debe contener un manifest.json válido en la raíz.');

            return false;
        }

        return true;
    }
}
