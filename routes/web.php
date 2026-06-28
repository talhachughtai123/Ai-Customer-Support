<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WidgetController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::patch('/conversations/{conversation}', [ConversationController::class, 'update'])->name('conversations.update');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('conversations.messages.store');
    Route::post('/conversations/{conversation}/typing', [ConversationController::class, 'typing'])->name('conversations.typing');
});

// Public website live-chat widget — no auth; the conversation token is the credential.
Route::prefix('widget')->name('widget.')->group(function () {
    Route::get('/', [WidgetController::class, 'show'])->name('show');
    Route::post('/conversations', [WidgetController::class, 'start'])->name('start');
    Route::get('/conversations/{token}', [WidgetController::class, 'thread'])->name('thread');
    Route::post('/conversations/{token}/messages', [WidgetController::class, 'storeMessage'])->name('messages.store');
    Route::post('/conversations/{token}/read', [WidgetController::class, 'markRead'])->name('read');
    Route::post('/conversations/{token}/typing', [WidgetController::class, 'typing'])->name('typing');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
