@php
    $user = auth()->user();
    $avatarDraftPreferences = $this->avatarPreferences();
@endphp

<div class="space-y-8">
    @if (session('profile-status'))
        <div role="status" class="rounded-md border border-border bg-background px-4 py-3 text-sm" aria-live="polite">
            {{ session('profile-status') }}
        </div>
    @endif

    <section class="space-y-5" aria-labelledby="account-heading">
        <div>
            <h2 id="account-heading" class="text-lg font-semibold">Resumen de cuenta</h2>
            <p class="mt-1 text-sm text-muted-foreground">Información esencial para mantener tu cuenta al día.</p>
        </div>
        <div class="divide-y divide-border border-y border-border">
            <div class="flex flex-col gap-3 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-full bg-accent text-base font-semibold text-accent-foreground">
                        @if ($photo && $profileMode === 'photo')
                            <img src="{{ $photo->temporaryUrl() }}" alt="Vista previa de la imagen de perfil"
                                class="size-full object-cover">
                        @else
                            <x-user-avatar :user="$user" size="size-full" :pixels="48" :seed="$profileMode === 'avatar' ? $selectedAvatarSeed : null"
                                animate="hover" alt="Imagen de perfil de {{ $user->name }}" class="rounded-full" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                        <p class="truncate text-sm text-muted-foreground">{{ $user->email }}</p>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            @forelse ($roles as $role)
                                <span
                                    class="rounded border border-border bg-muted px-2 py-0.5 text-xs font-medium">{{ $role }}</span>
                            @empty
                                <span class="text-xs text-muted-foreground">Sin rol asignado</span>
                            @endforelse
                        </div>
                    </div>
                </div>
                <a href="#profile-details"
                    class="min-h-11 self-start py-3 text-sm font-medium underline-offset-4 hover:underline sm:self-auto">Editar
                    perfil</a>
            </div>

            <div class="flex flex-col gap-3 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium">Verificación en dos pasos</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ $user->hasEnabledTwoFactorAuthentication() ? 'Activa para proteger tu inicio de sesión.' : 'Añade una capa extra de seguridad a tu cuenta.' }}
                    </p>
                </div>
                <a href="#two-factor-heading"
                    class="min-h-11 self-start py-3 text-sm font-medium underline-offset-4 hover:underline sm:self-auto">{{ $user->hasEnabledTwoFactorAuthentication() ? 'Administrar' : 'Activar' }}</a>
            </div>
        </div>
        <div class="border-l-2 border-primary pl-5" aria-labelledby="session-heading">
            <h3 id="session-heading" class="text-sm font-semibold">Sesión segura</h3>
            <p class="mt-1 text-sm leading-6 text-muted-foreground">Al cerrar sesión, tus páginas privadas no se
                conservarán en la caché del navegador.</p>
        </div>
    </section>

    <section id="profile-details" class="space-y-5 border-t border-border pt-7"
        aria-labelledby="profile-details-heading">
        <div>
            <h2 id="profile-details-heading" class="text-lg font-semibold">Perfil</h2>
            <p class="mt-1 text-sm text-muted-foreground">Actualiza el nombre y la imagen que aparecen en tu cuenta.</p>
        </div>
        <form wire:submit="updateProfile" class="grid max-w-2xl gap-5">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div
                    class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-full border border-border bg-accent text-2xl font-semibold text-accent-foreground">
                    @if ($photo && $profileMode === 'photo')
                        <img src="{{ $photo->temporaryUrl() }}" alt="Vista previa de la imagen de perfil"
                            class="size-full object-cover">
                    @else
                        <x-user-avatar :user="$user" size="size-full" :pixels="96" :seed="$profileMode === 'avatar' ? $selectedAvatarSeed : null"
                            :preferences="$avatarDraftPreferences" alt="Imagen de perfil actual" class="rounded-full"
                            data-avatar-customizer-preview data-avatar-customizer-primary />
                    @endif
                </div>
                <div class="grid gap-2">
                    <p class="text-sm font-medium">Imagen de perfil</p>
                    <div class="inline-flex w-fit rounded-md border border-input p-1" role="group"
                        aria-label="Tipo de imagen de perfil">
                        <button type="button" wire:click="selectProfilePhoto"
                            aria-pressed="{{ $profileMode === 'photo' ? 'true' : 'false' }}"
                            @class([
                                'min-h-10 rounded px-3 text-sm transition-colors hover:bg-accent',
                                'bg-accent font-medium' => $profileMode === 'photo',
                            ])>Imagen</button>
                        <button type="button" wire:click="selectProfileAvatar"
                            aria-pressed="{{ $profileMode === 'avatar' ? 'true' : 'false' }}"
                            @class([
                                'min-h-10 rounded px-3 text-sm transition-colors hover:bg-accent',
                                'bg-accent font-medium' => $profileMode === 'avatar',
                            ])>Avatar</button>
                    </div>

                    @if ($profileMode === 'photo')
                        <div class="grid max-w-md gap-3">
                            <label for="profile-photo" class="text-sm font-medium text-muted-foreground">Selecciona una
                                imagen
                                {{ $user->profile_photo_path ? 'nueva (opcional)' : '' }}</label>
                            <div class="file-upload-card">
                                <input id="profile-photo" data-filepond data-cropper="true" data-crop-ratio="1:1"
                                    wire:model="photo" type="file" accept="image/*" class="block w-full">
                            </div>
                            <p class="text-xs text-muted-foreground">PNG, JPG o WebP. Máximo 2 MB.</p>
                        </div>
                        @error('photo')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                        <div wire:loading wire:target="photo" class="text-sm text-muted-foreground" aria-live="polite">
                            Cargando imagen...</div>
                        @if ($user->profile_photo_path)
                            <button type="button" wire:click="removeProfilePhoto"
                                wire:confirm="¿Eliminar la imagen de perfil?"
                                class="min-h-11 self-start text-sm font-medium text-destructive underline-offset-4 hover:underline">Eliminar
                                imagen</button>
                        @endif
                    @endif
                </div>
            </div>

            @if ($profileMode === 'avatar')
                <fieldset class="grid gap-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <legend class="text-sm font-medium">Elige un avatar</legend>
                        <div class="flex flex-wrap gap-2">
                        <button type="button" data-avatar-customizer-open aria-haspopup="dialog"
                            class="inline-flex min-h-11 items-center gap-2 rounded-md border border-input bg-background px-3 text-sm font-medium transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                <path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6 7 7m10 10 1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.6" />
                            </svg>
                            Personalizar
                        </button>
                        <button type="button" wire:click="generateAvatarChoices" wire:loading.attr="disabled"
                            class="inline-flex min-h-10 items-center gap-2 rounded-md border border-input px-3 text-sm font-medium hover:bg-accent">
                            <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                <path
                                    d="M16 3h5v5m-1-4-5 5M8 8l8 8m0 0v-5m0 5h5M3 8h2.5a4 4 0 0 1 2.8 1.2M3 16h2.5a4 4 0 0 0 2.8-1.2"
                                    stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            Generar nuevos
                        </button>
                        </div>
                    </div>
                    <div wire:replace class="grid grid-cols-4 gap-2 sm:grid-cols-8 sm:gap-3">
                        @foreach ($avatarChoices as $seed)
                            <label wire:key="profile-avatar-{{ $seed }}" class="group cursor-pointer">
                                <input type="radio" wire:model="selectedAvatarSeed" data-avatar-seed-choice
                                    value="{{ $seed }}" class="peer sr-only"
                                    aria-label="Avatar {{ $loop->iteration }}">
                                <span
                                    class="flex min-h-20 flex-col items-center justify-center gap-1 rounded-md border border-border p-2 text-xs text-muted-foreground transition-colors group-hover:bg-accent peer-checked:border-primary peer-checked:bg-accent peer-focus-visible:outline-2 peer-focus-visible:outline-ring sm:min-h-24">
                                    <x-user-avatar :user="$user" :seed="$seed" :preferences="$avatarDraftPreferences"
                                        size="size-14" :pixels="56" alt="" class="rounded-full"
                                        data-avatar-customizer-preview />
                                    <span>{{ $loop->iteration }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedAvatarSeed')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror

                    <dialog data-avatar-customizer-dialog aria-labelledby="avatar-customizer-title"
                        class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-2xl border border-border bg-card p-0 text-card-foreground shadow-2xl backdrop:bg-black/60">
                        <div data-avatar-customizer class="grid max-h-[calc(100dvh-2rem)] overflow-y-auto md:grid-cols-[220px_minmax(0,1fr)]">
                            <aside class="grid content-center justify-items-center gap-4 border-b border-border bg-muted/40 p-6 md:border-b-0 md:border-r">
                                <div class="grid size-36 place-items-center overflow-hidden rounded-full border border-border bg-background shadow-sm">
                                    <x-user-avatar :user="$user" :seed="$selectedAvatarSeed" :preferences="$avatarDraftPreferences"
                                        size="size-full" :pixels="144" alt="Vista previa del avatar personalizado"
                                        data-avatar-customizer-preview data-avatar-customizer-primary />
                                </div>
                                <div class="text-center">
                                    <p class="text-sm font-semibold">Vista previa en tiempo real</p>
                                    <p class="mt-1 text-xs leading-5 text-muted-foreground">Los cambios se guardan al pulsar “Guardar perfil”.</p>
                                </div>
                            </aside>

                            <div class="grid gap-5 p-5 sm:p-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 id="avatar-customizer-title" class="text-lg font-semibold">Personalizar avatar</h3>
                                        <p class="mt-1 text-sm text-muted-foreground">Ajusta su color, expresión y fondo.</p>
                                    </div>
                                    <button type="button" data-avatar-customizer-close aria-label="Cerrar personalización"
                                        class="grid size-11 shrink-0 place-items-center rounded-full text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <svg viewBox="0 0 24 24" fill="none" class="size-5" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>
                                    </button>
                                </div>

                                <fieldset class="grid gap-2">
                                    <legend class="text-sm font-medium">Color del personaje</legend>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="cursor-pointer"><input type="radio" wire:model="avatarColorMode" value="auto" data-avatar-field="color_mode" class="peer sr-only"><span class="grid min-h-11 place-items-center rounded-md border border-input px-3 text-sm peer-checked:border-primary peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-ring">Automático</span></label>
                                        <label class="cursor-pointer"><input type="radio" wire:model="avatarColorMode" value="custom" data-avatar-field="color_mode" class="peer sr-only"><span class="grid min-h-11 place-items-center rounded-md border border-input px-3 text-sm peer-checked:border-primary peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-ring">Personalizado</span></label>
                                    </div>
                                    <label for="avatar-hue" class="mt-2 flex items-center justify-between text-sm"><span>Matiz</span><output data-avatar-hue-output>{{ $avatarHue }}°</output></label>
                                    <input id="avatar-hue" type="range" min="0" max="359" step="1" wire:model="avatarHue" data-avatar-field="hue" class="w-full accent-primary">
                                    <label for="avatar-tone" class="flex items-center justify-between text-sm"><span>Luminosidad</span><output data-avatar-tone-output>{{ round($avatarTone * 100) }}%</output></label>
                                    <input id="avatar-tone" type="range" min="0.15" max="0.85" step="0.01" wire:model="avatarTone" data-avatar-field="tone" class="w-full accent-primary">
                                </fieldset>

                                <div class="grid gap-2">
                                    <label for="avatar-expression" class="text-sm font-medium">Expresión</label>
                                    <select id="avatar-expression" wire:model="avatarExpression" data-avatar-field="expression" class="h-11 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                        <option value="idle">Neutral</option><option value="happy">Feliz</option><option value="wink">Guiño</option><option value="surprised">Sorprendido</option><option value="thinking">Pensativo</option><option value="sleepy">Soñoliento</option><option value="love">Enamorado</option><option value="shy">Tímido</option><option value="smug">Seguro</option><option value="unsure">Dudoso</option>
                                    </select>
                                </div>

                                <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                                    <div class="grid gap-2">
                                        <label for="avatar-background" class="text-sm font-medium">Forma del fondo</label>
                                        <select id="avatar-background" wire:model="avatarBackground" data-avatar-field="background" class="h-11 rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                            <option value="circle">Círculo</option><option value="squircle">Suave</option><option value="square">Cuadrado</option><option value="transparent">Transparente</option>
                                        </select>
                                    </div>
                                    <div class="grid gap-2">
                                        <label for="avatar-background-color" class="text-sm font-medium">Color de fondo</label>
                                        <input id="avatar-background-color" type="color" wire:model="avatarBackgroundColor" data-avatar-field="background_color" class="h-11 w-full min-w-20 cursor-pointer rounded-md border border-input bg-background p-1">
                                    </div>
                                </div>

                                <div class="flex justify-end border-t border-border pt-4">
                                    <button type="button" data-avatar-customizer-close class="min-h-11 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">Aplicar cambios</button>
                                </div>
                            </div>
                        </div>
                    </dialog>
                </fieldset>
            @endif

            <div class="grid max-w-xl gap-2">
                <label for="profile-name" class="text-sm font-medium">Nombre</label>
                <input id="profile-name" wire:model="name" type="text" autocomplete="name" maxlength="255"
                    required
                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-base shadow-sm outline-none transition-[box-shadow,border-color] focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                @error('name')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid max-w-xl gap-2">
                <label class="text-sm font-medium">Rol asignado</label>
                <div class="flex min-h-11 flex-wrap items-center gap-2 rounded-md border border-input bg-muted/50 px-3 py-2 text-sm"
                    aria-live="polite">
                    @forelse ($roles as $role)
                        <span
                            class="rounded border border-border bg-background px-2 py-0.5 text-xs font-medium">{{ $role }}</span>
                    @empty
                        <span class="text-muted-foreground">Sin rol asignado</span>
                    @endforelse
                </div>
            </div>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex min-h-11 items-center justify-center self-start rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-wait disabled:opacity-60">
                <span wire:loading.remove wire:target="updateProfile">Guardar perfil</span>
                <span wire:loading wire:target="updateProfile">Guardando...</span>
            </button>
        </form>

        <div class="border-t border-border pt-5" aria-labelledby="appearance-heading">
            <h3 id="appearance-heading" class="text-sm font-semibold">Apariencia</h3>
            <p class="mt-1 text-sm text-muted-foreground">Elige cómo se muestra Snippetdesk.</p>
            <div class="mt-3 flex flex-wrap gap-2" role="group" aria-label="Apariencia">
                <button type="button" data-theme-value="light" aria-pressed="false"
                    class="flex min-h-11 items-center gap-2 rounded-md border border-input bg-background px-3 text-sm transition-colors hover:bg-accent aria-pressed:bg-accent">
                    Claro
                    <svg data-theme-check="light" viewBox="0 0 24 24" fill="none" class="hidden size-4"
                        aria-hidden="true">
                        <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
                <button type="button" data-theme-value="dark" aria-pressed="false"
                    class="flex min-h-11 items-center gap-2 rounded-md border border-input bg-background px-3 text-sm transition-colors hover:bg-accent aria-pressed:bg-accent">
                    Oscuro
                    <svg data-theme-check="dark" viewBox="0 0 24 24" fill="none" class="hidden size-4"
                        aria-hidden="true">
                        <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
                <button type="button" data-theme-value="system" aria-pressed="false"
                    class="flex min-h-11 items-center gap-2 rounded-md border border-input bg-background px-3 text-sm transition-colors hover:bg-accent aria-pressed:bg-accent">
                    Sistema
                    <svg data-theme-check="system" viewBox="0 0 24 24" fill="none" class="hidden size-4"
                        aria-hidden="true">
                        <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>
    </section>

    <section class="space-y-5" aria-labelledby="password-heading">
        <div>
            <h2 id="password-heading" class="text-lg font-semibold">Cambiar contraseña</h2>
            <p class="mt-1 text-sm text-muted-foreground">Usa una contraseña nueva que no hayas usado antes.</p>
        </div>

        <form wire:submit="updatePassword" class="grid max-w-xl gap-5">
            <div class="grid gap-2">
                <label for="current-password" class="text-sm font-medium">Contraseña actual</label>
                <input id="current-password" wire:model="currentPassword" type="password"
                    autocomplete="current-password" required
                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-base shadow-sm outline-none transition-[box-shadow,border-color] focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                @error('currentPassword')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid gap-2">
                <label for="new-password" class="text-sm font-medium">Nueva contraseña</label>
                <input id="new-password" wire:model="newPassword" type="password" autocomplete="new-password"
                    required
                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-base shadow-sm outline-none transition-[box-shadow,border-color] focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                @error('newPassword')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid gap-2">
                <label for="new-password-confirmation" class="text-sm font-medium">Confirmar nueva contraseña</label>
                <input id="new-password-confirmation" wire:model="newPasswordConfirmation" type="password"
                    autocomplete="new-password" required
                    class="h-11 w-full rounded-md border border-input bg-background px-3 text-base shadow-sm outline-none transition-[box-shadow,border-color] focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                @error('newPasswordConfirmation')
                    <p class="text-sm text-destructive">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled"
                class="inline-flex min-h-11 items-center justify-center self-start rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-wait disabled:opacity-60">
                <span wire:loading.remove wire:target="updatePassword">Guardar contraseña</span>
                <span wire:loading wire:target="updatePassword">Guardando...</span>
            </button>
        </form>
    </section>

    <section class="space-y-5 border-t border-border pt-7" aria-labelledby="two-factor-heading">
        <div>
            <h2 id="two-factor-heading" class="text-lg font-semibold">Verificación en dos pasos</h2>
            <p class="mt-1 text-sm text-muted-foreground">Usa una aplicación de autenticación para confirmar tu
                identidad al
                iniciar sesión.</p>
        </div>

        @if (is_null($user->two_factor_secret))
            <form wire:submit="enableTwoFactor" class="grid max-w-xl gap-4">
                <div class="grid gap-2">
                    <label for="enable-two-factor-password" class="text-sm font-medium">Confirma tu contraseña</label>
                    <input id="enable-two-factor-password" wire:model="twoFactorPassword" type="password"
                        autocomplete="current-password" required
                        class="h-11 w-full rounded-md border border-input bg-background px-3 text-base shadow-sm outline-none transition-[box-shadow,border-color] focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                    @error('twoFactorPassword')
                        <p class="text-sm text-destructive">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" wire:loading.attr="disabled"
                    class="inline-flex min-h-11 items-center justify-center self-start rounded-md border border-input bg-background px-4 text-sm font-medium transition-colors hover:bg-accent disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="enableTwoFactor">Configurar verificación</span>
                    <span wire:loading wire:target="enableTwoFactor">Preparando...</span>
                </button>
            </form>
        @elseif (is_null($user->two_factor_confirmed_at))
            <div class="grid gap-6 border-y border-border py-6 md:grid-cols-[220px_minmax(0,1fr)]">
                <div class="grid size-52 place-items-center rounded-md border border-border bg-white p-3">
                    {!! $user->twoFactorQrCodeSvg() !!}
                </div>
                <div class="space-y-5">
                    <div>
                        <h3 class="text-sm font-semibold">1. Escanea el código QR</h3>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Añade esta cuenta a tu aplicación de
                            autenticación. Después, introduce el código de seis dígitos que aparece.</p>
                    </div>
                    <form wire:submit="confirmTwoFactor" class="grid max-w-sm gap-3">
                        <label for="two-factor-code" class="text-sm font-medium">Código de verificación</label>
                        <input id="two-factor-code" wire:model="code" type="text" inputmode="numeric"
                            autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required
                            class="h-11 w-full rounded-md border border-input bg-background px-3 font-mono text-base tracking-[0.15em] shadow-sm outline-none transition-[box-shadow,border-color] focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30">
                        @error('code')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                        <button type="submit" wire:loading.attr="disabled"
                            class="inline-flex min-h-11 items-center justify-center self-start rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:cursor-wait disabled:opacity-60">
                            <span wire:loading.remove wire:target="confirmTwoFactor">Confirmar y activar</span>
                            <span wire:loading wire:target="confirmTwoFactor">Verificando...</span>
                        </button>
                    </form>
                </div>
            </div>
            @if ($recoveryCodes)
                <div class="max-w-2xl space-y-3" aria-live="polite">
                    <h3 class="text-sm font-semibold">Códigos de recuperación</h3>
                    <p class="text-sm text-muted-foreground">Guárdalos en un lugar seguro. Cada código solo se puede
                        usar
                        una vez.</p>
                    <ul
                        class="grid gap-2 rounded-md border border-border bg-background p-4 font-mono text-sm sm:grid-cols-2">
                        @foreach ($recoveryCodes as $recoveryCode)
                            <li>{{ $recoveryCode }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <div
                class="flex flex-col gap-4 border-y border-border py-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span
                        class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-md bg-emerald-100 text-emerald-800"
                        aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" class="size-4">
                            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-medium">La verificación en dos pasos está activa</p>
                        <p class="mt-1 text-sm text-muted-foreground">Tu cuenta solicita un código de autenticación al
                            iniciar sesión.</p>
                    </div>
                </div>
                <form wire:submit="disableTwoFactor" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div class="grid gap-2">
                        <label for="disable-two-factor-password" class="sr-only">Confirma tu contraseña para
                            desactivar</label>
                        <input id="disable-two-factor-password" wire:model="twoFactorPassword" type="password"
                            autocomplete="current-password" placeholder="Contraseña actual" required
                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-base shadow-sm outline-none transition-[box-shadow,border-color] focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 sm:w-52 sm:text-sm">
                        @error('twoFactorPassword')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled"
                        class="min-h-11 rounded-md border border-destructive/40 px-3 text-sm font-medium text-destructive transition-colors hover:bg-destructive/5 disabled:cursor-wait disabled:opacity-60">Desactivar</button>
                </form>
            </div>

            <div class="grid max-w-xl gap-4">
                <div>
                    <h3 class="text-sm font-semibold">Códigos de recuperación</h3>
                    <p class="mt-1 text-sm text-muted-foreground">Genera un nuevo conjunto si ya no tienes acceso a tus
                        códigos anteriores.</p>
                </div>
                <form wire:submit="regenerateRecoveryCodes"
                    class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start">
                    <div class="grid gap-2">
                        <label for="recovery-codes-password" class="text-sm font-medium">Confirma tu
                            contraseña</label>
                        <input id="recovery-codes-password" wire:model="twoFactorPassword" type="password"
                            autocomplete="current-password" required
                            class="h-11 w-full rounded-md border border-input bg-background px-3 text-base shadow-sm outline-none transition-[box-shadow,border-color] focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/30 sm:text-sm">
                        @error('twoFactorPassword')
                            <p class="text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" wire:loading.attr="disabled"
                        class="mt-0 min-h-11 self-end rounded-md border border-input bg-background px-3 text-sm font-medium transition-colors hover:bg-accent disabled:cursor-wait disabled:opacity-60">Generar
                        códigos</button>
                </form>
            </div>
            @if ($recoveryCodes)
                <ul class="grid max-w-xl gap-2 rounded-md border border-border bg-background p-4 font-mono text-sm sm:grid-cols-2"
                    aria-live="polite">
                    @foreach ($recoveryCodes as $recoveryCode)
                        <li>{{ $recoveryCode }}</li>
                    @endforeach
                </ul>
            @endif
        @endif
    </section>
</div>
