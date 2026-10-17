<?php

namespace App\Http\Middleware;

use App\Models\SidebarItem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSidebarModuleIsActive
{
    public function handle(Request $request, Closure $next, string $routeName): Response
    {
        $items = SidebarItem::query()
            ->with('parent:id,is_active,permission_name')
            ->where('route_name', $routeName)
            ->get();

        // Routes remain available before the navigation catalog is seeded.
        if ($items->isEmpty()) {
            return $next($request);
        }

        $isAvailable = $items->contains(
            fn (SidebarItem $item): bool => $item->isAccessibleTo($request->user()),
        );

        abort_unless($isAvailable, 403, 'No tienes acceso a este módulo.');

        return $next($request);
    }
}
