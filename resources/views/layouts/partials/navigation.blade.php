@php
    $sidebarItems = \App\Models\SidebarItem::query()
        ->with(['children' => fn($query) => $query->where('is_active', true)->ordered()])
        ->whereNull('parent_id')
        ->where('is_active', true)
        ->ordered()
        ->get()
        ->filter(fn($item) => blank($item->permission_name) || auth()->user()->can($item->permission_name));
@endphp

<nav class="space-y-6 px-3 py-5" aria-label="Navegación principal">
    @foreach ($sidebarItems as $item)
        @php
            $children = $item->children->filter(
                fn($child) => blank($child->permission_name) || auth()->user()->can($child->permission_name),
            );
            $activeRoute =
                $children->first(fn($child) => $child->active_route && request()->routeIs($child->active_route))
                    ?->active_route ??
                ($item->active_route ?? $item->route_name);
            $isActive = $activeRoute && request()->routeIs($activeRoute);
        @endphp

        <section class="space-y-1">
            @if ($item->type === 'group')
                @if ($children->isNotEmpty())
                    <p class="sidebar-section-title px-3 pb-1 text-xs font-medium text-muted-foreground">
                        {{ $item->label }}</p>
                    @foreach ($children as $child)
                        @php($childActive = ($child->active_route ?? $child->route_name) && request()->routeIs($child->active_route ?? $child->route_name))
                        @include('layouts.partials.sidebar-item', [
                            'item' => $child,
                            'active' => $childActive,
                        ])
                    @endforeach
                @endif
            @else
                @include('layouts.partials.sidebar-item', ['item' => $item, 'active' => $isActive])
            @endif
        </section>
    @endforeach
</nav>
