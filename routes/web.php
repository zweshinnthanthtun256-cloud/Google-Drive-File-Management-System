<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return inertia('Login');
});


/*
|--------------------------------------------------------------------------
| Google Authentication
|--------------------------------------------------------------------------
*/

Route::get(
    '/auth/google',
    [GoogleAuthController::class, 'redirect']
)->name('google.login');

Route::get(
    '/auth/google/callback',
    [GoogleAuthController::class, 'callback']
)->name('google.callback');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Google Drive
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/drive',
        [DriveController::class, 'index']
    )->name('drive.index');

    Route::post(
        '/drive/upload',
        [DriveController::class, 'upload']
    )->name('drive.upload');

    Route::get(
        '/drive/{fileId}/download',
        [DriveController::class, 'download']
    )->name('drive.download');

    Route::delete(
        '/drive/{fileId}',
        [DriveController::class, 'delete']
    )->name('drive.delete');


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/logout',
        [GoogleAuthController::class, 'logout']
    )->name('logout');
});