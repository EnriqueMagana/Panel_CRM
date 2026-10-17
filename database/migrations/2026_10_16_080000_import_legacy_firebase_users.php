<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'legacy_firebase_uid')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('legacy_firebase_uid', 128)->nullable()->unique()->after('id');
            });
        }
        if (! Schema::hasColumn('users', 'legacy_firebase_role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('legacy_firebase_role', 40)->nullable()->after('legacy_firebase_uid');
            });
        }
        if (! Schema::hasColumn('users', 'legacy_firebase_imported')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('legacy_firebase_imported')->default(false)->after('legacy_firebase_role');
            });
        }

        if (app()->environment('testing')) {
            return;
        }

        $users = $this->payload();
        $agentRole = Role::findOrCreate('Agente', 'web');
        $permissions = [
            'dashboard.view',
            'preset_messages.view',
            'preset_messages.create',
            'preset_messages.update',
            'preset_messages.delete',
            'ticket_escalations.view',
            'ticket_escalations.create',
            'ticket_escalations.update',
            'ticket_escalations.delete',
            'browser_extensions.view',
        ];

        foreach ($permissions as $permissionName) {
            $agentRole->givePermissionTo(Permission::findOrCreate($permissionName, 'web'));
        }

        DB::transaction(function () use ($users, $agentRole): void {
            foreach ($users as $legacyUser) {
                $existing = DB::table('users')
                    ->whereRaw('LOWER(email) = ?', [$legacyUser['email']])
                    ->first();

                if ($existing && $existing->legacy_firebase_uid && $existing->legacy_firebase_uid !== $legacyUser['firebase_uid']) {
                    throw new RuntimeException("El correo {$legacyUser['email']} ya esta asociado a otro UID de Firebase.");
                }

                if ($existing) {
                    DB::table('users')->where('id', $existing->id)->update([
                        'legacy_firebase_uid' => $legacyUser['firebase_uid'],
                        'legacy_firebase_role' => $legacyUser['source_role'],
                        'updated_at' => now(),
                    ]);
                    $userId = $existing->id;
                } else {
                    $userId = DB::table('users')->insertGetId([
                        'legacy_firebase_uid' => $legacyUser['firebase_uid'],
                        'legacy_firebase_role' => $legacyUser['source_role'],
                        'legacy_firebase_imported' => true,
                        'name' => $legacyUser['name'],
                        'username' => $this->uniqueUsername($legacyUser['email']),
                        'email' => $legacyUser['email'],
                        'email_verified_at' => $legacyUser['email_verified'] ? $legacyUser['created_at'] : null,
                        'password' => Hash::make(Str::random(64)),
                        'status' => $legacyUser['disabled'] ? 'suspended' : 'invited',
                        'avatar_seed' => $this->uuidFromFirebaseUid($legacyUser['firebase_uid']),
                        'created_at' => $legacyUser['created_at'],
                        'updated_at' => $legacyUser['created_at'],
                    ]);
                }

                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $agentRole->id,
                    'model_type' => User::class,
                    'model_id' => $userId,
                ]);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! app()->environment('testing')) {
            $legacyUsers = DB::table('users')->whereNotNull('legacy_firebase_uid')->get(['id', 'legacy_firebase_imported']);
            $importedUserIds = $legacyUsers->where('legacy_firebase_imported', true)->pluck('id')->all();
            $matchedUserIds = $legacyUsers->where('legacy_firebase_imported', false)->pluck('id')->all();

            if ($importedUserIds !== []) {
                DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->whereIn('model_id', $importedUserIds)
                    ->delete();
            }

            $agentRoleId = DB::table('roles')->where('name', 'Agente')->where('guard_name', 'web')->value('id');
            if ($agentRoleId && $matchedUserIds !== []) {
                DB::table('model_has_roles')
                    ->where('role_id', $agentRoleId)
                    ->where('model_type', User::class)
                    ->whereIn('model_id', $matchedUserIds)
                    ->delete();
            }

            DB::table('users')->where('legacy_firebase_imported', true)->delete();
            DB::table('users')->whereNotNull('legacy_firebase_uid')->update([
                'legacy_firebase_uid' => null,
                'legacy_firebase_role' => null,
                'legacy_firebase_imported' => false,
            ]);
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_legacy_firebase_uid_unique');
            $table->dropColumn(['legacy_firebase_uid', 'legacy_firebase_role', 'legacy_firebase_imported']);
        });
    }

    /** @return list<array<string, mixed>> */
    private function payload(): array
    {
        $encoded = <<<'PAYLOAD'
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
PAYLOAD;

        return json_decode(gzdecode(base64_decode(preg_replace('/\s+/u', '', $encoded))), true, flags: JSON_THROW_ON_ERROR);
    }

    private function uniqueUsername(string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '.') ?: 'user';
        $base = mb_substr($base, 0, 220);
        $username = $base;
        $suffix = 1;

        while (DB::table('users')->where('username', $username)->exists()) {
            $username = $base.'.'.$suffix++;
        }

        return $username;
    }

    private function uuidFromFirebaseUid(string $uid): string
    {
        $hex = md5('snippetdesk-firebase:'.$uid);
        $hex[12] = '5';
        $hex[16] = dechex((hexdec($hex[16]) & 0x3) | 0x8);

        return sprintf('%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
};
