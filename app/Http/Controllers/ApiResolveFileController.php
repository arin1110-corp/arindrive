<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\DriveFile;
use Illuminate\Http\Request;

class ApiResolveFileController extends Controller
{
    public function resolve(Request $request)
    {
        $token = str_replace('Bearer ', '', $request->header('Authorization'));

        $apiClient = ApiClient::where('token', $token)
            ->where('is_active', true)
            ->first();

        if (!$apiClient) {
            return response()->json([
                'success' => false,
                'message' => 'API token tidak valid.',
            ], 401);
        }

        $request->validate([
            'url' => 'required|string',
        ]);

        $fileUid = $this->extractFileUid($request->url);

        if (!$fileUid) {
            return response()->json([
                'success' => false,
                'message' => 'File UID tidak ditemukan dari URL.',
            ], 422);
        }

        $file = DriveFile::where('file_uid', $fileUid)->first();

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'Data file tidak ditemukan di ArinDrive.',
            ], 404);
        }

        $googleUrl = 'https://drive.google.com/file/d/' . $file->google_file_id . '/view';

        return response()->json([
            'success' => true,
            'data' => [
                'file_uid' => $file->file_uid,
                'google_file_id' => $file->google_file_id,
                'google_url' => $googleUrl,
                'name' => $file->name,
            ],
        ]);
    }

    private function extractFileUid(string $url): ?string
    {
        if (preg_match('/\/f\/([^\/\?\#]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}