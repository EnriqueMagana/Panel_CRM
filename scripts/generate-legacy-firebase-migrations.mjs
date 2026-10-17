import { readFile, writeFile } from 'node:fs/promises';
import { gzipSync } from 'node:zlib';
import { resolve } from 'node:path';

const exportPath = resolve(process.argv[2] || 'storage/app/private/legacy-firebase-export.json');
const exportData = JSON.parse(await readFile(exportPath, 'utf8'));

const normalizeTimestamp = (value, fallback) => {
    if (value === null || value === undefined || value === '') value = fallback;
    const numeric = Number(value);
    const date = Number.isFinite(numeric)
        ? new Date(numeric > 10_000_000_000 ? numeric : numeric * 1000)
        : new Date(value);
    if (Number.isNaN(date.getTime())) {
        const fallbackDate = new Date(fallback);
        return fallbackDate.toISOString().slice(0, 19).replace('T', ' ');
    }

    return date.toISOString().slice(0, 19).replace('T', ' ');
};

const fallbackTimestamp = exportData.exported_at || new Date().toISOString();
const users = exportData.users.map((user) => ({
    firebase_uid: String(user.firebase_uid),
    email: String(user.email || `${user.firebase_uid}@legacy.snippetdesk.invalid`).trim().toLowerCase(),
    name: String(user.name || user.email || 'Usuario').trim().slice(0, 255) || 'Usuario',
    email_verified: Boolean(user.email_verified),
    disabled: Boolean(user.disabled),
    source_role: String(user.role || 'user').slice(0, 40),
    created_at: normalizeTimestamp(user.auth_created_at || user.firestore_created_at, fallbackTimestamp),
}));

const messages = [];
for (const user of exportData.users) {
    const pinned = (user.messages || [])
        .filter((message) => Boolean(message.isPinned || message.pinned))
        .sort((left, right) => Number(right.pinOrder || 0) - Number(left.pinOrder || 0));
    const pinOrder = new Map(pinned.map((message, index) => [String(message.firebase_id), index + 1]));

    for (const message of user.messages || []) {
        const createdAt = normalizeTimestamp(message.created_at, fallbackTimestamp);
        messages.push({
            firebase_uid: String(user.firebase_uid),
            firebase_id: String(message.firebase_id),
            title: String(message.titulo || '').trim().slice(0, 120),
            body: String(message.descripcion || '').trim(),
            pinned: pinOrder.has(String(message.firebase_id)),
            pin_order: pinOrder.get(String(message.firebase_id)) || null,
            created_at: createdAt,
            updated_at: normalizeTimestamp(message.updated_at, createdAt),
        });
    }
}

const encodedPayload = (value) => gzipSync(Buffer.from(JSON.stringify(value)))
    .toString('base64')
    .match(/.{1,100}/gu)
    .join('\n');

const usersPayload = encodedPayload(users);
const messagesPayload = encodedPayload(messages);

const usersMigration = `<?php

use App\\Models\\User;
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Hash;
use Illuminate\\Support\\Facades\\Schema;
use Illuminate\\Support\\Str;
use Spatie\\Permission\\Models\\Permission;
use Spatie\\Permission\\Models\\Role;
use Spatie\\Permission\\PermissionRegistrar;

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
${usersPayload}
PAYLOAD;

        return json_decode(gzdecode(base64_decode(preg_replace('/\\s+/u', '', $encoded))), true, flags: JSON_THROW_ON_ERROR);
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
`;

const messagesMigration = `<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('preset_messages', 'legacy_firebase_path')) {
            Schema::table('preset_messages', function (Blueprint $table): void {
                $table->string('legacy_firebase_path', 320)->nullable()->unique()->after('id');
            });
        }

        if (app()->environment('testing')) {
            return;
        }

        $usersByUid = DB::table('users')
            ->whereNotNull('legacy_firebase_uid')
            ->pluck('id', 'legacy_firebase_uid');

        DB::transaction(function () use ($usersByUid): void {
            foreach (array_chunk($this->payload(), 200) as $messages) {
                foreach ($messages as $legacyMessage) {
                    $userId = $usersByUid[$legacyMessage['firebase_uid']] ?? null;
                    if (! $userId) {
                        throw new RuntimeException("No existe el usuario Firebase {$legacyMessage['firebase_uid']}.");
                    }

                    $path = 'users/'.$legacyMessage['firebase_uid'].'/mensajes/'.$legacyMessage['firebase_id'];
                    preg_match_all('/\\{\\{\\s*([A-Za-z][A-Za-z0-9_]*)\\s*\\}\\}/u', $legacyMessage['body'], $matches);

                    DB::table('preset_messages')->updateOrInsert(
                        ['legacy_firebase_path' => $path],
                        [
                            'created_by' => $userId,
                            'title' => $legacyMessage['title'],
                            'body' => $legacyMessage['body'],
                            'variables' => json_encode(array_values(array_unique($matches[1] ?? [])), JSON_UNESCAPED_UNICODE),
                            'is_active' => true,
                            'created_at' => $legacyMessage['created_at'],
                            'updated_at' => $legacyMessage['updated_at'],
                        ],
                    );

                    if ($legacyMessage['pinned']) {
                        $messageId = DB::table('preset_messages')->where('legacy_firebase_path', $path)->value('id');
                        DB::table('preset_message_user')->updateOrInsert(
                            ['preset_message_id' => $messageId, 'user_id' => $userId],
                            [
                                'pinned_at' => $legacyMessage['updated_at'],
                                'pin_order' => $legacyMessage['pin_order'],
                                'hidden_at' => null,
                                'created_at' => $legacyMessage['created_at'],
                                'updated_at' => $legacyMessage['updated_at'],
                            ],
                        );
                    }
                }
            }
        });
    }

    public function down(): void
    {
        if (! app()->environment('testing')) {
            DB::table('preset_messages')->whereNotNull('legacy_firebase_path')->delete();
        }

        Schema::table('preset_messages', function (Blueprint $table): void {
            $table->dropUnique('preset_messages_legacy_firebase_path_unique');
            $table->dropColumn('legacy_firebase_path');
        });
    }

    /** @return list<array<string, mixed>> */
    private function payload(): array
    {
        $encoded = <<<'PAYLOAD'
${messagesPayload}
PAYLOAD;

        return json_decode(gzdecode(base64_decode(preg_replace('/\\s+/u', '', $encoded))), true, flags: JSON_THROW_ON_ERROR);
    }
};
`;

const usersMigrationPath = resolve('database/migrations/2026_10_16_080000_import_legacy_firebase_users.php');
const messagesMigrationPath = resolve('database/migrations/2026_10_16_081000_import_legacy_firebase_preset_messages.php');
await writeFile(usersMigrationPath, usersMigration);
await writeFile(messagesMigrationPath, messagesMigration);

process.stdout.write(JSON.stringify({
    users: users.length,
    messages: messages.length,
    users_migration: usersMigrationPath,
    messages_migration: messagesMigrationPath,
}));
