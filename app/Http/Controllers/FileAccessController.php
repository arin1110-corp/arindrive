<?php

namespace App\Http\Controllers;

use App\Models\DriveFile;
use App\Services\GoogleDriveService;
use Google\Service\Drive;

class FileAccessController extends Controller
{
    public function show($file_uid, GoogleDriveService $google)
    {
        $file = DriveFile::with('driveAccount')
            ->where('file_uid', $file_uid)
            ->firstOrFail();

        $client = $google->clientFromAccount($file->driveAccount);

        $drive = new Drive($client);

        $response = $drive->files->get(
            $file->google_file_id,
            ['alt' => 'media']
        );

        $content = $response->getBody()->getContents();

        return response($content, 200)
            ->header('Content-Type', $file->mime_type ?: 'application/octet-stream')
            ->header('Content-Disposition', 'inline; filename="' . $file->name . '"');
    }
}