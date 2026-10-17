<div class="mx-auto w-full max-w-7xl space-y-6">
    <header class="flex flex-col gap-5 border-b border-border pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm font-medium text-primary">Identidad y acceso</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">Herramientas IAM</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground">
                Administra políticas, dispositivos con sesión vigente y eventos de seguridad de Support+.
            </p>
        </div>
        <span class="inline-flex min-h-10 w-fit items-center gap-2 rounded-full border border-border bg-muted/40 px-3 text-xs font-medium text-muted-foreground">
            <span class="size-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
            Sin polling · actualización manual
        </span>
    </header>

    @if (session('technical-center-status'))
        <div class="rounded-lg border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300" role="status" aria-live="polite">
            {{ session('technical-center-status') }}
        </div>
    @endif

    <nav class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" aria-label="Herramientas IAM">
        <button type="button" wire:click="selectTool('policies')" aria-pressed="{{ $activeTool === 'policies' ? 'true' : 'false' }}"
            class="flex min-h-20 items-center gap-3 rounded-xl border p-4 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $activeTool === 'policies' ? 'border-primary bg-primary/5 text-foreground' : 'border-border bg-card text-muted-foreground hover:bg-accent' }}">
            <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true"><path d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.6"/></svg>
            </span>
            <span><span class="block text-sm font-semibold text-foreground">Políticas de acceso</span><span class="mt-1 block text-xs">Sesiones y contraseñas</span></span>
        </button>

        @can('technical_center.view_sessions')
            <button type="button" wire:click="selectTool('sessions')" aria-pressed="{{ $activeTool === 'sessions' ? 'true' : 'false' }}"
                class="flex min-h-20 items-center gap-3 rounded-xl border p-4 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $activeTool === 'sessions' ? 'border-primary bg-primary/5 text-foreground' : 'border-border bg-card text-muted-foreground hover:bg-accent' }}">
                <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M8 20h8m-4-4v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span><span class="block text-sm font-semibold text-foreground">Sesiones y dispositivos</span><span class="mt-1 block text-xs">Consulta y cierre remoto</span></span>
            </button>
        @endcan

        @can('technical_center.view_audit')
            <button type="button" wire:click="selectTool('audit')" aria-pressed="{{ $activeTool === 'audit' ? 'true' : 'false' }}"
                class="flex min-h-20 items-center gap-3 rounded-xl border p-4 text-left transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring {{ $activeTool === 'audit' ? 'border-primary bg-primary/5 text-foreground' : 'border-border bg-card text-muted-foreground hover:bg-accent' }}">
                <span class="grid size-11 shrink-0 place-items-center rounded-lg bg-primary/10 text-primary">
                    <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16l-7-3-7 3V5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8.5 8h7M8.5 12h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <span><span class="block text-sm font-semibold text-foreground">Bitácora IAM</span><span class="mt-1 block text-xs">Trazabilidad y exportación</span></span>
            </button>
        @endcan
    </nav>

    @if ($activeTool === 'policies')
        <section class="overflow-hidden rounded-xl border border-border bg-card shadow-sm" aria-labelledby="iam-policy-heading">
            <div class="border-b border-border px-5 py-5 sm:px-6">
                <h2 id="iam-policy-heading" class="text-lg font-semibold">Políticas IAM</h2>
                <p class="mt-1 text-sm text-muted-foreground">Los cambios se aplican a las siguientes solicitudes; las variables del servidor permanecen como respaldo.</p>
            </div>
            <form wire:submit="saveIamSettings" class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-4">
                @foreach ([
                    ['idleTimeoutMinutes', 'IAM_IDLE_TIMEOUT_MINUTES', 'Inactividad', 'minutos'],
                    ['absoluteTimeoutMinutes', 'IAM_ABSOLUTE_TIMEOUT_MINUTES', 'Duración absoluta', 'minutos'],
                    ['sessionWarningSeconds', 'IAM_SESSION_WARNING_SECONDS', 'Aviso previo', 'segundos'],
                    ['passwordMinLength', 'IAM_PASSWORD_MIN_LENGTH', 'Longitud mínima', 'caracteres'],
                ] as [$model, $code, $label, $unit])
                    <label class="grid gap-2 text-sm font-medium">
                        <span>{{ $label }}</span>
                        <code class="text-[11px] font-normal text-muted-foreground">{{ $code }}</code>
                        <div class="relative">
                            <input type="number" wire:model="{{ $model }}" class="h-11 w-full rounded-md border border-input bg-background px-3 pr-20 outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <span class="pointer-events-none absolute right-3 top-3 text-xs text-muted-foreground">{{ $unit }}</span>
                        </div>
                        @error($model)<span class="text-xs text-destructive" role="alert">{{ $message }}</span>@enderror
                    </label>
                @endforeach

                <label class="flex items-start gap-3 rounded-lg border border-border bg-muted/30 p-4 sm:col-span-2 xl:col-span-4">
                    <input type="checkbox" wire:model="checkCompromisedPasswords" class="mt-1 size-4 accent-primary">
                    <span><span class="block text-sm font-semibold">Bloquear contraseñas comprometidas</span><span class="mt-1 block text-xs leading-5 text-muted-foreground">Valida credenciales expuestas. Requiere acceso saliente a internet desde producción.</span></span>
                </label>

                <div class="flex flex-wrap items-center gap-3 border-t border-border pt-5 sm:col-span-2 xl:col-span-4">
                    @can('technical_center.manage_iam')
                        <button class="min-h-11 rounded-md bg-primary px-5 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-60" wire:loading.attr="disabled" wire:target="saveIamSettings">Guardar políticas</button>
                    @endcan
                    @if ($saved)<span class="text-sm font-medium text-emerald-600 dark:text-emerald-300">Configuración guardada.</span>@endif
                    <span wire:loading wire:target="saveIamSettings" class="text-sm text-muted-foreground">Guardando…</span>
                </div>
            </form>
        </section>
    @endif

    @if ($activeTool === 'sessions')
        @can('technical_center.view_sessions')
            <section class="overflow-hidden rounded-xl border border-border bg-card shadow-sm" aria-labelledby="sessions-heading">
                <div class="flex flex-col gap-4 border-b border-border px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 id="sessions-heading" class="text-lg font-semibold">Sesiones y dispositivos</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ $sessions->count() }} {{ $sessions->count() === 1 ? 'dispositivo activo' : 'dispositivos activos' }}. Se agrupan usuario, navegador e IP repetidos.</p>
                    </div>
                    <button type="button" wire:click="$refresh" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent disabled:opacity-60">
                        <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.34 5.66M20 4v7h-7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Actualizar ahora
                    </button>
                </div>
                <div class="border-b border-border bg-muted/25 px-5 py-3 text-xs leading-5 text-muted-foreground sm:px-6">
                    No hay consultas automáticas. Solo se consideran activas las sesiones con actividad dentro de los últimos {{ $idleTimeoutMinutes }} minutos.
                </div>
                <div class="grid gap-4 p-4 sm:p-6 lg:grid-cols-2">
                    @forelse ($sessions as $activeSession)
                        <article class="flex min-w-0 flex-col justify-between gap-5 rounded-xl border border-border bg-background p-4" wire:key="session-{{ $activeSession['id'] }}">
                            <div class="flex min-w-0 items-start gap-3">
                                @if ($activeSession['user'])<x-user-avatar :user="$activeSession['user']" size="size-11" :pixels="44" alt="" class="rounded-full" />@endif
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-semibold">{{ $activeSession['user']?->name ?? 'Usuario eliminado' }}</p>
                                        @if($activeSession['current'])<span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:text-emerald-300">Esta sesión</span>@endif
                                        @if($activeSession['instances'] > 1)<span class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground">{{ $activeSession['instances'] }} registros agrupados</span>@endif
                                    </div>
                                    <p class="mt-2 text-xs text-muted-foreground">{{ $activeSession['device'] }} · {{ $activeSession['browser'] }}</p>
                                    <p class="mt-1 break-all text-xs text-muted-foreground">IP {{ $activeSession['ip'] }}</p>
                                    <p class="mt-1 text-xs text-muted-foreground">Última actividad: {{ $activeSession['last_activity']->diffForHumans() }}</p>
                                </div>
                            </div>
                            @can('technical_center.revoke_sessions')
                                @unless($activeSession['current'])
                                    <button type="button" wire:click="revokeSession('{{ $activeSession['id'] }}')" wire:confirm="¿Cerrar todas las sesiones de este dispositivo?" wire:loading.attr="disabled" wire:target="revokeSession('{{ $activeSession['id'] }}')" class="min-h-11 w-full rounded-md border border-destructive/30 px-4 text-sm font-medium text-destructive hover:bg-destructive/10 disabled:opacity-60 sm:w-fit">Cerrar dispositivo</button>
                                @endunless
                            @endcan
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-border bg-muted/20 px-6 py-12 text-center lg:col-span-2">
                            <h3 class="font-semibold">No hay dispositivos activos</h3>
                            <p class="mt-1 text-sm text-muted-foreground">Las sesiones vencidas ya no se incluyen en esta lista.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        @endcan
    @endif

    @if ($activeTool === 'audit')
        @can('technical_center.view_audit')
            <section class="overflow-hidden rounded-xl border border-border bg-card shadow-sm" aria-labelledby="audit-heading">
                <div class="flex flex-col gap-3 border-b border-border px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div><h2 id="audit-heading" class="text-lg font-semibold">Bitácora IAM</h2><p class="mt-1 text-sm text-muted-foreground">Últimos 100 eventos. La vista se actualiza únicamente al abrirla o al pulsar actualizar.</p></div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="$refresh" class="min-h-11 rounded-md border border-input px-4 text-sm font-medium hover:bg-accent">Actualizar ahora</button>
                        @can('technical_center.export_audit')<button type="button" wire:click="exportAudit" class="min-h-11 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90">Exportar CSV</button>@endcan
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground"><tr><th class="px-5 py-3">Fecha</th><th class="px-5 py-3">Evento</th><th class="px-5 py-3">Actor</th><th class="px-5 py-3">Usuario afectado</th><th class="px-5 py-3">IP</th></tr></thead>
                        <tbody class="divide-y divide-border">
                            @forelse($auditLogs as $log)
                                <tr><td class="whitespace-nowrap px-5 py-3 text-muted-foreground">{{ $log->created_at?->format('d/m/Y H:i') }}</td><td class="px-5 py-3"><p class="font-medium">{{ $log->description }}</p><code class="text-[11px] text-muted-foreground">{{ $log->event }}</code></td><td class="px-5 py-3">{{ $log->actor?->name ?? 'Sistema' }}</td><td class="px-5 py-3">{{ $log->subject?->name ?? '—' }}</td><td class="px-5 py-3 text-muted-foreground">{{ $log->ip_address ?? '—' }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-10 text-center text-muted-foreground">Aún no hay eventos IAM.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endcan
    @endif
</div>
