<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo json_encode([
    'legacy_users' => DB::table('users')->whereNotNull('legacy_firebase_uid')->count(),
    'imported_users' => DB::table('users')->where('legacy_firebase_imported', true)->count(),
    'legacy_messages' => DB::table('preset_messages')->whereNotNull('legacy_firebase_path')->count(),
    'orphaned_messages' => DB::table('preset_messages')
        ->leftJoin('users', 'users.id', '=', 'preset_messages.created_by')
        ->whereNotNull('preset_messages.legacy_firebase_path')
        ->whereNull('users.id')
        ->count(),
    'pinned_messages' => DB::table('preset_message_user')->whereNotNull('pinned_at')->count(),
    'placeholder_emails' => DB::table('users')->where('email', 'like', '%@legacy.snippetdesk.invalid')->count(),
    'assigned_roles' => DB::table('model_has_roles')->count(),
], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT).PHP_EOL;
