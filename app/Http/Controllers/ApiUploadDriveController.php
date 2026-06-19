<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\DriveAccount;
use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\Request;

class ApiUploadDriveController extends Controller
{
    public function upload(Request $request, GoogleDriveService $google)
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
            'file' => 'required|file|max:2048000',
            'folder_id' => 'required|string',
            'filename' => 'required|string|max:255',
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

        $file = $request->file('file');

        try {
            $drive = new Drive(
                $google->clientFromAccount($account)
            );

            $this->deleteOldFileByBaseName(
                $drive,
                $request->folder_id,
                pathinfo($request->filename, PATHINFO_FILENAME)
            );

            $metadata = new DriveFile([
                'name' => $request->filename,
                'parents' => [$request->folder_id],
            ]);

            $created = $drive->files->create($metadata, [
                'data' => file_get_contents($file->getRealPath()),
                'mimeType' => $file->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id,name,webViewLink,webContentLink,mimeType,size',
            ]);

            $permission = new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);

            $drive->permissions->create($created->id, $permission);

            $apiClient->update([
                'last_used_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil diupload ke Google Drive.',
                'data' => [
                    'google_file_id' => $created->id,
                    'name' => $created->name,
                    'mime_type' => $created->mimeType,
                    'size' => $created->size,
                    'url' => $created->webViewLink ?: 'https://drive.google.com/file/d/' . $created->id . '/view',
                    'drive_account' => $account->email,
                    'folder_id' => $request->folder_id,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload ke Google Drive: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function deleteOldFileByBaseName(Drive $drive, string $folderId, string $baseName): void
    {
        $safeBaseName = str_replace("'", "\\'", $baseName);

        $files = $drive->files->listFiles([
            'q' => "'{$folderId}' in parents and trashed = false and name contains '{$safeBaseName}'",
            'fields' => 'files(id,name)',
        ]);

        foreach ($files->files as $file) {
            $drive->files->delete($file->id);
        }
    }
}