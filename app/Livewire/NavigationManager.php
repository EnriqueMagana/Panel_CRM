<?php

namespace App\Livewire;

use App\Models\SidebarItem;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Livewire\Component;

class NavigationManager extends Component
{
    public bool $formOpen = false;

    public ?int $editingItemId = null;

    public ?int $parentId = null;

    public string $type = 'link';

    public string $label = '';

    public string $icon = '';

    public string $routeName = '';

    public string $routeFragment = '';

    public string $actionKey = '';

    public string $permissionName = '';

    public string $activeRoute = '';

    public int $sortOrder = 10;

    public bool $isActive = true;

    public function createItem(): void
    {
        $this->authorize('navigation.manage');
        $this->resetForm();
        $this->formOpen = true;
    }

    public function editItem(int $itemId): void
    {
        $this->authorize('navigation.manage');
        $item = SidebarItem::findOrFail($itemId);

        $this->resetForm();
        $this->editingItemId = $item->id;
        $this->parentId = $item->parent_id;
        $this->type = $item->type;
        $this->label = $item->label;
        $this->icon = $item->icon ?? '';
        $this->routeName = $item->route_name ?? '';
        $this->routeFragment = $item->route_fragment ?? '';
        $this->actionKey = $item->action_key ?? '';
        $this->permissionName = $item->permission_name ?? '';
        $this->activeRoute = $item->active_route ?? '';
        $this->sortOrder = $item->sort_order;
        $this->isActive = $item->is_active;
        $this->formOpen = true;
    }

    public function saveItem(): void
    {
        $this->authorize('navigation.manage');

        $item = $this->editingItemId ? SidebarItem::findOrFail($this->editingItemId) : null;
        $isGroup = $this->type === 'group';
        $isAction = $this->type === 'action';
        $permissionNames = collect(config('access.modules'))
            ->flatMap(fn (array $module, string $key) => collect($module['permissions'])
                ->keys()
                ->map(fn (string $action) => "{$key}.{$action}"))
            ->all();

        $validated = $this->validate([
            'parentId' => ['nullable', 'integer', Rule::exists('sidebar_items', 'id')->where('type', 'group')],
            'type' => ['required', Rule::in(['group', 'link', 'action'])],
            'label' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', Rule::in(array_keys(config('sidebar.icons')))],
            'routeName' => [$isGroup || $isAction ? 'nullable' : 'required', 'string', Rule::in(array_keys(Route::getRoutes()->getRoutesByName()))],
            'routeFragment' => ['nullable', 'string', 'max:100'],
            'actionKey' => [$isAction ? 'required' : 'nullable', Rule::in(array_keys(config('sidebar.actions')))],
            'permissionName' => ['nullable', Rule::in($permissionNames)],
            'activeRoute' => ['nullable', 'string', 'max:100'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:65535'],
            'isActive' => ['boolean'],
        ]);

        if ($validated['parentId'] && $validated['type'] === 'group') {
            $this->addError('type', 'Los grupos no pueden anidarse dentro de otro grupo.');

            return;
        }

        if ($item && (int) $validated['parentId'] === $item->id) {
            $this->addError('parentId', 'El elemento no puede ser su propio grupo padre.');

            return;
        }

        $data = [
            'parent_id' => $validated['parentId'],
            'type' => $validated['type'],
            'label' => $validated['label'],
            'icon' => $validated['icon'] ?: null,
            'route_name' => $validated['routeName'] ?: null,
            'route_fragment' => $validated['routeFragment'] ?: null,
            'action_key' => $validated['actionKey'] ?: null,
            'permission_name' => $validated['permissionName'] ?: null,
            'active_route' => $validated['activeRoute'] ?: null,
            'sort_order' => $validated['sortOrder'],
            'is_active' => $validated['isActive'],
        ];

        if ($item && $item->type === 'group' && $item->children()->exists() && $data['type'] !== 'group') {
            $this->addError('type', 'Mueve o elimina primero los elementos hijos de este grupo.');

            return;
        }

        $item ? $item->update($data) : SidebarItem::create($data);

        $this->closeForm();
        session()->flash('navigation-status', $item ? 'Elemento actualizado.' : 'Elemento creado.');
    }

    public function deleteItem(int $itemId): void
    {
        $this->authorize('navigation.manage');

        SidebarItem::findOrFail($itemId)->delete();
        session()->flash('navigation-status', 'Elemento eliminado.');
    }

    public function cancelForm(): void
    {
        $this->closeForm();
    }

    public function render()
    {
        return view('livewire.navigation-manager', [
            'items' => SidebarItem::query()->with('children')->whereNull('parent_id')->ordered()->get(),
            'groups' => SidebarItem::query()->where('type', 'group')->ordered()->get(),
            'permissions' => collect(config('access.modules'))
                ->flatMap(fn (array $module, string $key) => collect($module['permissions'])
                    ->keys()
                    ->mapWithKeys(fn (string $action) => ["{$key}.{$action}" => "{$module['label']}: {$action}"]))
                ->all(),
        ]);
    }

    private function closeForm(): void
    {
        $this->formOpen = false;
        $this->reset(
            'editingItemId',
            'parentId',
            'type',
            'label',
            'icon',
            'routeName',
            'routeFragment',
            'actionKey',
            'permissionName',
            'activeRoute',
            'sortOrder',
            'isActive',
        );
        $this->type = 'link';
        $this->sortOrder = 10;
        $this->isActive = true;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->closeForm();
    }
}
