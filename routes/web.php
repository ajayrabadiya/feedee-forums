<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::view('/rules', 'pages.rules')->name('rules');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/support', 'pages.support')->name('support');

Route::view('/create-post', 'pages.create-post')->name('posts.create');
Route::view('/p/{post}', 'pages.post')->where('post', '[A-Za-z0-9]+')->name('posts.show');

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/', 'admin.dashboard')->name('dashboard');
    Route::view('/posts', 'admin.posts')->name('posts');
    Route::view('/reports', 'admin.reports')->name('reports');
    Route::view('/docs', 'admin.docs')->name('docs');
});
