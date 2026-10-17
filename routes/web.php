<?php

use App\Http\Controllers\BrowserExtensionDownloadController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PublicMediaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::middleware('auth')->group(function () {
    Route::get('/media/{path}', PublicMediaController::class)
        ->where('path', '.*')
        ->name('media.public');

    Route::view('/home', 'home')->middleware(['can:dashboard.view', 'sidebar.active:home'])->name('home');
    Route::view('/profile', 'profile')->middleware('sidebar.active:profile')->name('profile');
    Route::view('/users', 'users')->middleware(['can:users.view', 'sidebar.active:users'])->name('users');
    Route::view('/roles', 'roles')->middleware(['can:roles.view', 'sidebar.active:roles'])->name('roles');
    Route::view('/navigation', 'navigation')->middleware(['can:navigation.view', 'sidebar.active:navigation'])->name('navigation');
    Route::view('/technical-center', 'technical-center')
        ->middleware(['can:technical_center.view', 'sidebar.active:technical-center'])
        ->name('technical-center');
    Route::view('/preset-messages', 'preset-messages')
        ->middleware(['can:preset_messages.view', 'sidebar.active:preset-messages'])
        ->name('preset-messages');
    Route::view('/ticket-escalations', 'ticket-escalations')
        ->middleware(['can:ticket_escalations.view', 'sidebar.active:ticket-escalations'])
        ->name('ticket-escalations');
    Route::view('/extensions/browser', 'browser-extension')
        ->middleware(['can:browser_extensions.view', 'sidebar.active:browser-extension'])
        ->name('browser-extension');
    Route::get('/extensions/browser/{browserExtension}/download', BrowserExtensionDownloadController::class)
        ->middleware(['can:browser_extensions.view', 'sidebar.active:browser-extension'])
        ->name('browser-extensions.download');

    Route::middleware('sidebar.active:chats')->group(function () {
        Route::view('/chats', 'chats')->name('chats');
        Route::delete('/chats/{conversation}', [ChatController::class, 'destroyConversation'])->name('chat.conversation.destroy');
        Route::post('/chats/direct', [ChatController::class, 'storeDirect'])->name('chat.direct.store');
        Route::post('/chats/groups', [ChatController::class, 'storeGroup'])->name('chat.groups.store');
        Route::post('/chats/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');
        Route::put('/chats/{conversation}/messages/{message}', [ChatController::class, 'updateMessage'])->name('chat.messages.update');
        Route::delete('/chats/{conversation}/messages/{message}', [ChatController::class, 'destroyMessage'])->name('chat.messages.destroy');
    });
});
