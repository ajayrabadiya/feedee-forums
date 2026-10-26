<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\PostModerationController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\StoreIntendedUrl;
use Illuminate\Support\Facades\Route;

Route::get('/', [PostController::class, 'index'])->name('home');
Route::view('/rules', 'pages.rules')->name('rules');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/support', 'pages.support')->name('support');

Route::get('/p/{post}', [PostController::class, 'show'])->where('post', '[A-Za-z0-9]+')->name('posts.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->middleware(StoreIntendedUrl::class)->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->middleware(StoreIntendedUrl::class)->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:6,1')->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/create-post', [PostController::class, 'create'])->name('posts.create');
    Route::post('/create-post', [PostController::class, 'store'])->middleware('throttle:10,1')->name('posts.store');
    Route::get('/p/{post:public_id}/edit', [PostController::class, 'edit'])->name('posts.edit');
    Route::put('/p/{post:public_id}', [PostController::class, 'update'])->middleware('throttle:10,1')->name('posts.update');
    Route::delete('/p/{post:public_id}', [PostController::class, 'destroy'])->name('posts.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::get('/posts', [PostModerationController::class, 'index'])->name('posts');
    Route::post('/posts/moderate', [PostModerationController::class, 'moderate'])->middleware('throttle:60,1')->name('posts.moderate');
    Route::put('/settings/post-approval', [SettingController::class, 'updatePostApproval'])->name('settings.post-approval');
    Route::view('/reports', 'admin.reports')->name('reports');
    Route::get('/customers', CustomerController::class)->name('customers');
    Route::view('/docs', 'admin.docs')->name('docs');
});
