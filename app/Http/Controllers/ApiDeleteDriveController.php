<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\DriveFile;
use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Illuminate\Http\Request;

class ApiDeleteDriveController extends Controller
{
    public function delete(Request $request, GoogleDriveService $google)
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
            'reference_id' => 'required|string',
        ]);

        $driveFile = DriveFile::where(
            'reference_id',
            $request->reference_id
        )->first();

        if (!$driveFile) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan.',
            ], 404);
        }

        try {

            $drive = new Drive(
                $google->clientFromAccount(
                    $driveFile->driveAccount
                )
            );

            $drive->files->delete(
                $driveFile->google_file_id
            );

            $driveFile->delete();

            $apiClient->update([
                'last_used_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil dihapus.',
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ],500);

        }
    }
}