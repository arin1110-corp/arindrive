<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\DriveAccount;
use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Illuminate\Http\Request;

class ApiMoveDriveFileController extends Controller
{
    public function move(Request $request, GoogleDriveService $google)
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
            'google_file_id' => 'required|string',
            'folder_id' => 'required|string',
            'drive_account_id' => 'nullable|integer',
        ]);

        $account = $request->drive_account_id
            ? DriveAccount::where('id', $request->drive_account_id)->where('is_active', true)->first()
            : DriveAccount::where('is_active', true)->latest()->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Google Drive aktif tidak ditemukan.',
            ], 422);
        }

        try {
            $drive = new Drive($google->clientFromAccount($account));

            $file = $drive->files->get($request->google_file_id, [
                'fields' => 'id,name,parents,webViewLink',
            ]);

            $previousParents = $file->parents
                ? implode(',', $file->parents)
                : null;

            $updated = $drive->files->update(
                $request->google_file_id,
                null,
                [
                    'addParents' => $request->folder_id,
                    'removeParents' => $previousParents,
                    'fields' => 'id,name,parents,webViewLink',
                ]
            );

            $apiClient->update([
                'last_used_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil dipindahkan.',
                'data' => [
                    'google_file_id' => $updated->id,
                    'name' => $updated->name,
                    'url' => $updated->webViewLink ?: 'https://drive.google.com/file/d/' . $updated->id . '/view',
                    'folder_id' => $request->folder_id,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal pindah file: ' . $e->getMessage(),
            ], 500);
        }
    }
}