<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\GoogleController;

Route::get('/', [DriveController::class, 'index'])->name('dashboard');

Route::get('/google/connect', [GoogleController::class, 'connectForm'])->name('google.connect');
Route::post('/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/google/callback', [GoogleController::class, 'callback'])->name('google.callback');

Route::post('/upload', [DriveController::class, 'upload'])->name('drive.upload');
Route::get('/sync-storage', [DriveController::class, 'syncStorage'])->name('drive.sync');