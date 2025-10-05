<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        return redirect()->intended(route('starter'));
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
})->name('login.store')->middleware('guest');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/login');
})->name('logout')->middleware('auth');

// Redirect root to main app
Route::get('/', function () {
    return redirect()->route('starter');
})->name('home');

// Main sourdough app routes - protected by authentication
Route::middleware('auth')->group(function () {
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
});
