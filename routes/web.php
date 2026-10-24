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
