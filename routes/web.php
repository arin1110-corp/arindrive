<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\DriveGroupController;
use App\Http\Controllers\ApiClientController;
use App\Http\Controllers\FileAccessController;

Route::get('/login', [AdminAuthController::class, 'loginForm'])->name('admin.login');
Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.process');


Route::get('/f/{file_uid}', [FileAccessController::class, 'show'])->name('files.show');

Route::middleware('admin.only')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::get('/', [DriveController::class, 'index'])->name('dashboard');

    Route::get('/google/connect', [GoogleController::class, 'connectForm'])->name('google.connect');
    Route::post('/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

    Route::post('/upload', [DriveController::class, 'upload'])->name('drive.upload');
    Route::get('/sync-storage', [DriveController::class, 'syncStorage'])->name('drive.sync');

    Route::post('/groups', [DriveGroupController::class, 'store'])->name('groups.store');
    Route::put('/groups/{group}', [DriveGroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [DriveGroupController::class, 'destroy'])->name('groups.destroy');

    Route::post('/accounts/{id}/toggle', [DriveController::class, 'toggleAccount'])->name('accounts.toggle');
    Route::delete('/accounts/{id}', [DriveController::class, 'deleteAccount'])->name('accounts.delete');

    Route::post('/api-clients', [ApiClientController::class, 'store'])->name('api-clients.store');
    Route::post('/api-clients/{client}/toggle', [ApiClientController::class, 'toggle'])->name('api-clients.toggle');
    Route::delete('/api-clients/{client}', [ApiClientController::class, 'destroy'])->name('api-clients.destroy');
    Route::get(
        '/google/reconnect/{id}',
        [GoogleController::class, 'reconnect']
    )->name('google.reconnect');
});