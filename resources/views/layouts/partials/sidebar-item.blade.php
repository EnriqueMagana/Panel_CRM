@if ($item->type === 'action' && $item->action_key === 'search')
    <button type="button" data-search-trigger title="{{ $item->label }}"
        class="sidebar-link flex min-h-11 w-full items-center gap-3 rounded-md px-3 text-left text-sm font-medium text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
        <x-sidebar-icon :name="$item->icon" />
        <span class="sidebar-label">{{ $item->label }}</span>
    </button>
@elseif ($item->type === 'link' && $item->route_name && \Illuminate\Support\Facades\Route::has($item->route_name))
    <a href="{{ route($item->route_name, [], false) }}{{ $item->route_fragment ? '#' . $item->route_fragment : '' }}"
        wire:navigate.hover
        title="{{ $item->label }}" @class([
            'sidebar-link flex min-h-11 items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
            'bg-sidebar-accent text-sidebar-accent-foreground' => $active,
        ])
        @if ($active) aria-current="page" @endif>
        <x-sidebar-icon :name="$item->icon" />
        <span class="sidebar-label">{{ $item->label }}</span>
    </a>
@endif
