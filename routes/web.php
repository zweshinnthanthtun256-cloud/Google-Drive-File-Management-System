<?php

use App\Http\Controllers\DriveController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('drive.index');
    }

    return inertia('Login');
});

Route::get(
    '/auth/google',
    [GoogleAuthController::class, 'redirect']
)->name('google.login');

Route::get(
    '/auth/google/callback',
    [GoogleAuthController::class, 'callback']
)->name('google.callback');

Route::middleware('auth')->group(function () {

    Route::get(
        '/drive',
        [DriveController::class, 'index']
    )->name('drive.index');

    Route::get(
        '/drive/{fileId}/download',
        [DriveController::class, 'download']
    )->name('drive.download');

    Route::post(
        '/drive/upload',
        [DriveController::class, 'upload']
    )->name('drive.upload');
    Route::delete(
        '/drive/{fileId}',
        [DriveController::class, 'delete']
    )->name('drive.delete');

    Route::post(
        '/logout',
        [GoogleAuthController::class, 'logout']
    )->name('logout');
});
