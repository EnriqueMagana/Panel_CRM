<?php

namespace App\Livewire;

use App\Models\SidebarItem;
use Illuminate\Support\Facades\DB;
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

    public bool $isActive = true;

    public function createItem(): void
    {
        $this->authorize('navigation.manage');
        $this->resetForm();
        $this->formOpen = true;
    }

    public function createGroup(): void
    {
        $this->authorize('navigation.manage');
        $this->resetForm();
        $this->type = 'group';
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
            'routeName' => [$isGroup || $isAction ? 'nullable' : 'required', 'string', Rule::in(array_keys(config('sidebar.routes')))],
            'routeFragment' => ['nullable', 'string', 'max:100'],
            'actionKey' => [$isAction ? 'required' : 'nullable', Rule::in(array_keys(config('sidebar.actions')))],
            'permissionName' => ['nullable', Rule::in($permissionNames)],
            'activeRoute' => ['nullable', 'string', 'max:100'],
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

        $parentId = $isGroup ? null : ($validated['parentId'] ?? null);
        $sortOrder = $item && $item->parent_id === $parentId
            ? $item->sort_order
            : $this->nextSortOrder($parentId);

        $data = [
            'parent_id' => $parentId,
            'type' => $validated['type'],
            'label' => $validated['label'],
            'icon' => $validated['icon'] ?: null,
            'route_name' => $isGroup || $isAction ? null : ($validated['routeName'] ?: null),
            'route_fragment' => $isGroup || $isAction ? null : ($validated['routeFragment'] ?: null),
            'action_key' => $isAction ? ($validated['actionKey'] ?: null) : null,
            'permission_name' => $validated['permissionName'] ?: null,
            'active_route' => $isGroup || $isAction ? null : ($validated['activeRoute'] ?: null),
            'sort_order' => $sortOrder,
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

    public function toggleActive(int $itemId): void
    {
        $this->authorize('navigation.manage');

        $item = SidebarItem::findOrFail($itemId);
        $item->update(['is_active' => ! $item->is_active]);

        session()->flash(
            'navigation-status',
            $item->is_active ? 'Elemento activado.' : 'Elemento desactivado.',
        );
    }

    public function moveUp(int $itemId): void
    {
        $this->move($itemId, -1);
    }

    public function moveDown(int $itemId): void
    {
        $this->move($itemId, 1);
    }

    public function cancelForm(): void
    {
        $this->closeForm();
    }

    public function render()
    {
        $user = auth()->user();
        $canManage = $user->can('navigation.manage');
        $items = SidebarItem::query()->with('children')->whereNull('parent_id')->ordered()->get();

        if (! $canManage) {
            $items = $items
                ->map(function (SidebarItem $item) use ($user): SidebarItem {
                    $item->setRelation(
                        'children',
                        $item->children->filter(
                            fn (SidebarItem $child): bool => $child->is_active
                                && $child->isPermittedTo($user, false),
                        )->values(),
                    );

                    return $item;
                })
                ->filter(fn (SidebarItem $item): bool => $item->isAccessibleTo($user)
                    && ($item->type !== 'group' || $item->children->isNotEmpty()))
                ->values();
        }

        return view('livewire.navigation-manager', [
            'items' => $items,
            'groups' => $canManage
                ? SidebarItem::query()->where('type', 'group')->ordered()->get()
                : collect(),
            'routes' => config('sidebar.routes'),
            'permissions' => collect(config('access.modules'))
                ->flatMap(fn (array $module, string $key) => collect($module['permissions'])
                    ->keys()
                    ->mapWithKeys(fn (string $action) => ["{$key}.{$action}" => "{$module['label']}: {$action}"]))
                ->all(),
            'canManage' => $canManage,
        ]);
    }

    private function move(int $itemId, int $direction): void
    {
        $this->authorize('navigation.manage');

        DB::transaction(function () use ($itemId, $direction): void {
            $item = SidebarItem::query()->lockForUpdate()->findOrFail($itemId);
            $siblings = SidebarItem::query()
                ->where('parent_id', $item->parent_id)
                ->lockForUpdate()
                ->ordered()
                ->get()
                ->values();
            $currentIndex = $siblings->search(fn (SidebarItem $sibling): bool => $sibling->is($item));

            if ($currentIndex === false) {
                return;
            }

            $targetIndex = $currentIndex + $direction;

            if (! $siblings->has($targetIndex)) {
                return;
            }

            $current = $siblings[$currentIndex];
            $siblings[$currentIndex] = $siblings[$targetIndex];
            $siblings[$targetIndex] = $current;

            $siblings->each(
                fn (SidebarItem $sibling, int $index) => $sibling->update(['sort_order' => ($index + 1) * 10]),
            );
        });

        session()->flash('navigation-status', 'Orden actualizado.');
    }

    private function nextSortOrder(?int $parentId): int
    {
        $lastOrder = SidebarItem::query()
            ->where('parent_id', $parentId)
            ->max('sort_order');

        return min(((int) $lastOrder) + 10, 65535);
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
            'isActive',
        );
        $this->type = 'link';
        $this->isActive = true;
        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->closeForm();
    }
}
