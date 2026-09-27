<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\PublicMediaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/home');

Route::middleware('auth')->group(function () {
    Route::get('/media/{path}', PublicMediaController::class)
        ->where('path', '.*')
        ->name('media.public');

    Route::view('/home', 'home')->middleware('can:dashboard.view')->name('home');
    Route::view('/profile', 'profile')->name('profile');
    Route::view('/users', 'users')->middleware('can:users.view')->name('users');
    Route::view('/roles', 'roles')->middleware('can:roles.view')->name('roles');
    Route::view('/navigation', 'navigation')->middleware('can:navigation.view')->name('navigation');
    Route::view('/technical-center', 'technical-center')
        ->middleware('can:technical_center.view')
        ->name('technical-center');
    Route::view('/chats', 'chats')->name('chats');
    Route::delete('/chats/{conversation}', [ChatController::class, 'destroyConversation'])->name('chat.conversation.destroy');
    Route::post('/chats/direct', [ChatController::class, 'storeDirect'])->name('chat.direct.store');
    Route::post('/chats/groups', [ChatController::class, 'storeGroup'])->name('chat.groups.store');
    Route::post('/chats/{conversation}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');
    Route::put('/chats/{conversation}/messages/{message}', [ChatController::class, 'updateMessage'])->name('chat.messages.update');
    Route::delete('/chats/{conversation}/messages/{message}', [ChatController::class, 'destroyMessage'])->name('chat.messages.destroy');
});
