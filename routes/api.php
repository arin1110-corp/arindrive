<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiUploadController;
use App\Http\Controllers\ApiUploadDriveController;
use App\Http\Controllers\ApiResolveFileController;
use App\Http\Controllers\ApiMoveDriveFileController;
use App\Http\Controllers\ApiDeleteDriveController;
use App\Http\Controllers\ApiListDriveController;

Route::post('/delete-drive', [ApiDeleteDriveController::class, 'delete']);

Route::post('/upload', [ApiUploadController::class, 'upload']);

Route::post('/upload-drive-spj', [ApiUploadDriveController::class, 'uploadSPJ']);

Route::post('/upload-drive', [ApiUploadDriveController::class, 'upload']);

Route::post('/move-drive-file', [ApiMoveDriveFileController::class, 'move']);

Route::post('/resolve-file', [ApiResolveFileController::class, 'resolve']);

Route::post('/list-drive-files', [ApiListDriveController::class, 'list']);