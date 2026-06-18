<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiUploadController;

Route::post('/upload', [ApiUploadController::class, 'upload']);