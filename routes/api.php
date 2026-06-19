<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiUploadController;
use App\Http\Controllers\ApiUploadDriveController;


Route::post('/upload', [ApiUploadController::class, 'upload']);

Route::post('/upload-drive', [ApiUploadDriveController::class, 'upload']);