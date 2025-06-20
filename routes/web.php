<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\KnowledgeBaseArticleController;
use App\Http\Controllers\DashboardController; // New controller for main dashboard
use App\Http\Controllers\GeminiTestController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('knowledge-base', KnowledgeBaseArticleController::class);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/messages/{message}', [DashboardController::class, 'showMessage'])->name('messages.show');
    Route::post('/messages/{message}/suggest-reply', [DashboardController::class, 'suggestReply'])->name('messages.suggestReply');
    Route::post('/messages/{message}/update-status', [DashboardController::class, 'updateMessageStatus'])->name('messages.updateStatus');
    Route::post('/messages/{message}/classify', [DashboardController::class, 'reclassifyMessage'])->name('messages.reclassify');

     // New route for Gemini Test UI
     Route::get('/gemini-test', [GeminiTestController::class, 'index'])->name('gemini.test.ui');
     Route::post('/gemini-test/process', [GeminiTestController::class, 'processMessage'])->name('gemini.test.process');
});

require __DIR__.'/auth.php';
