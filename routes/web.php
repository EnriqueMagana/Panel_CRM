<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::middleware('auth')->group(function () {
    Route::post('/settings/preferences', function (Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'theme' => ['nullable', 'string', 'in:system,light,dark'],
            'sidebar_variant' => ['nullable', 'string', 'in:sidebar,inset,floating'],
            'layout' => ['nullable', 'string', 'in:default,compact,full'],
            'direction' => ['nullable', 'string', 'in:ltr,rtl'],
        ]);

        $user = auth()->user();
        $user->forceFill($validated)->save();

        return response()->json(['ok' => true]);
    })->name('settings.preferences');

    Route::view('/home', 'home')->middleware('can:dashboard.view')->name('home');
    Route::view('/profile', 'profile')->name('profile');
    Route::view('/users', 'users')->middleware('can:users.view')->name('users');
    Route::view('/roles', 'roles')->middleware('can:roles.view')->name('roles');
    Route::view('/navigation', 'navigation')->middleware('can:navigation.view')->name('navigation');
    Route::get('/chats', [\App\Http\Controllers\ChatController::class, 'index'])->name('chats');
    Route::delete('/chats/{conversation}', [\App\Http\Controllers\ChatController::class, 'destroyConversation'])->name('chat.conversation.destroy');
    Route::post('/chats/direct', [\App\Http\Controllers\ChatController::class, 'storeDirect'])->name('chat.direct.store');
    Route::post('/chats/groups', [\App\Http\Controllers\ChatController::class, 'storeGroup'])->name('chat.groups.store');
    Route::post('/chats/{conversation}/messages', [\App\Http\Controllers\ChatController::class, 'storeMessage'])->name('chat.messages.store');
    Route::put('/chats/{conversation}/messages/{message}', [\App\Http\Controllers\ChatController::class, 'updateMessage'])->name('chat.messages.update');
    Route::delete('/chats/{conversation}/messages/{message}', [\App\Http\Controllers\ChatController::class, 'destroyMessage'])->name('chat.messages.destroy');
});
