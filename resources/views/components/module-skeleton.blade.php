@props(['type' => 'dashboard'])

<div data-module-loader class="hidden" data-module-skeleton="{{ $type }}" aria-live="polite" aria-busy="true">
    <span class="sr-only">Cargando contenido…</span>
    <div class="module-skeleton rounded-2xl border border-border bg-background p-5 shadow-sm sm:p-6">
        <div class="module-skeleton-variant module-skeleton-dashboard">
            <div class="mb-6 flex items-center justify-between gap-3">
                <div class="skeleton-block h-4 w-28 rounded-full" aria-hidden="true"></div>
                <div class="skeleton-block h-10 w-36 rounded-xl" aria-hidden="true"></div>
            </div>
            <div class="space-y-4">
                <div class="skeleton-block h-9 w-72 rounded-full" aria-hidden="true"></div>
                <div class="skeleton-block h-4 w-full rounded-full" aria-hidden="true"></div>
                <div class="skeleton-block h-4 w-5/6 rounded-full" aria-hidden="true"></div>
            </div>
            <div class="mt-6 space-y-3">
                <div class="skeleton-block h-12 w-full rounded-xl" aria-hidden="true"></div>
                <div class="skeleton-block h-12 w-full rounded-xl" aria-hidden="true"></div>
                <div class="skeleton-block h-12 w-full rounded-xl" aria-hidden="true"></div>
            </div>
            <div class="mt-6 rounded-2xl border border-border bg-muted/20 p-4">
                <div class="skeleton-block h-5 w-40 rounded-full" aria-hidden="true"></div>
                <div class="mt-4 space-y-3">
                    <div class="skeleton-block h-4 w-full rounded-full" aria-hidden="true"></div>
                    <div class="skeleton-block h-4 w-11/12 rounded-full" aria-hidden="true"></div>
                    <div class="skeleton-block h-4 w-10/12 rounded-full" aria-hidden="true"></div>
                </div>
            </div>
        </div>

        <div class="module-skeleton-variant module-skeleton-profile">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="skeleton-block h-16 w-16 rounded-full" aria-hidden="true"></div>
                    <div class="space-y-2">
                        <div class="skeleton-block h-4 w-28 rounded-full" aria-hidden="true"></div>
                        <div class="skeleton-block h-4 w-36 rounded-full" aria-hidden="true"></div>
                    </div>
                </div>
                <div class="skeleton-block h-10 w-28 rounded-xl" aria-hidden="true"></div>
            </div>
            <div class="space-y-4">
                <div class="skeleton-block h-10 w-40 rounded-full" aria-hidden="true"></div>
                <div class="grid gap-3 sm:grid-cols-[160px_1fr] sm:items-center">
                    <div class="skeleton-block h-16 w-full rounded-xl" aria-hidden="true"></div>
                    <div class="space-y-2">
                        <div class="skeleton-block h-4 w-full rounded-full" aria-hidden="true"></div>
                        <div class="skeleton-block h-4 w-5/6 rounded-full" aria-hidden="true"></div>
                    </div>
                </div>
                <div class="grid gap-3">
                    <div class="skeleton-block h-11 w-full rounded-xl" aria-hidden="true"></div>
                    <div class="skeleton-block h-11 w-full rounded-xl" aria-hidden="true"></div>
                    <div class="skeleton-block h-11 w-full rounded-xl" aria-hidden="true"></div>
                </div>
            </div>
        </div>

        <div class="module-skeleton-variant module-skeleton-table">
            <div class="mb-6 flex items-center justify-between gap-3">
                <div class="skeleton-block h-5 w-32 rounded-full" aria-hidden="true"></div>
                <div class="skeleton-block h-10 w-28 rounded-xl" aria-hidden="true"></div>
            </div>
            <div class="space-y-3">
                <div class="skeleton-block h-12 w-full rounded-xl" aria-hidden="true"></div>
                <div class="skeleton-block h-12 w-full rounded-xl" aria-hidden="true"></div>
                <div class="skeleton-block h-12 w-full rounded-xl" aria-hidden="true"></div>
                <div class="skeleton-block h-12 w-full rounded-xl" aria-hidden="true"></div>
            </div>
        </div>

        <div class="module-skeleton-variant module-skeleton-list">
            <div class="space-y-4">
                <div class="skeleton-block h-5 w-40 rounded-full" aria-hidden="true"></div>
                <div class="space-y-3">
                    <div class="skeleton-block h-16 w-full rounded-xl" aria-hidden="true"></div>
                    <div class="skeleton-block h-16 w-full rounded-xl" aria-hidden="true"></div>
                    <div class="skeleton-block h-16 w-full rounded-xl" aria-hidden="true"></div>
                </div>
            </div>
        </div>

        <div class="module-skeleton-variant module-skeleton-chat">
            <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
                <div class="space-y-3">
                    <div class="skeleton-block h-10 w-full rounded-xl" aria-hidden="true"></div>
                    <div class="space-y-3">
                        <div class="skeleton-block h-16 w-full rounded-2xl" aria-hidden="true"></div>
                        <div class="skeleton-block h-16 w-full rounded-2xl" aria-hidden="true"></div>
                        <div class="skeleton-block h-16 w-full rounded-2xl" aria-hidden="true"></div>
                    </div>
                </div>
                <div class="space-y-4 rounded-2xl border border-border bg-muted/10 p-4">
                    <div class="flex items-center gap-3">
                        <div class="skeleton-block h-12 w-12 rounded-full" aria-hidden="true"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton-block h-4 w-32 rounded-full" aria-hidden="true"></div>
                            <div class="skeleton-block h-3 w-24 rounded-full" aria-hidden="true"></div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="skeleton-block h-12 w-2/5 rounded-2xl" aria-hidden="true"></div>
                        <div class="ml-auto skeleton-block h-12 w-2/5 rounded-2xl" aria-hidden="true"></div>
                        <div class="skeleton-block h-12 w-2/3 rounded-2xl" aria-hidden="true"></div>
                        <div class="ml-auto skeleton-block h-12 w-1/2 rounded-2xl" aria-hidden="true"></div>
                    </div>
                    <div class="skeleton-block h-12 w-full rounded-2xl" aria-hidden="true"></div>
                </div>
            </div>
        </div>
    </div>
</div>
