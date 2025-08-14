<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Redirect root to main app
Route::get('/', function () {
    return redirect()->route('starter');
})->name('home');

// Main sourdough app routes
Route::get('/starter', function () {
    return view('starter');
})->name('starter');

Route::get('/feeding', function () {
    return view('feeding');
})->name('feeding');

Route::get('/recipe', function () {
    return view('recipe');
})->name('recipe');

Route::get('/history', function () {
    return view('history');
})->name('history');

// Keep dashboard for backwards compatibility, redirect to starter
Route::get('/dashboard', function () {
    return redirect()->route('starter');
})->name('dashboard');

