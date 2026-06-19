<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiUploadController;
use App\Http\Controllers\ApiUploadDriveController;
use App\Http\Controllers\ApiResolveFileController;
use App\Http\Controllers\ApiMoveDriveFileController;


Route::post('/upload', [ApiUploadController::class, 'upload']);

Route::post('/upload-drive', [ApiUploadDriveController::class, 'upload']);

Route::post('/move-drive-file', [ApiMoveDriveFileController::class, 'move']);


Route::post('/resolve-file', [ApiResolveFileController::class, 'resolve']);