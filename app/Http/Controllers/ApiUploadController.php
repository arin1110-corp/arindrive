<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\DriveFile;
use App\Services\DriveAllocator;
use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile as GoogleDriveFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiUploadController extends Controller
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
            'source_app' => 'nullable|string|max:100',
            'folder' => 'nullable|string|max:150',
            'reference_id' => 'nullable|string|max:150',
        ]);

        $account = $request->drive_account_id
            ? DriveAccount::where('id', $request->drive_account_id)
            ->where('is_active', true)
            ->first()
            : DriveAccount::where('is_active', true)
            ->latest()
            ->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Google Drive aktif tidak ditemukan.',
            ], 422);
        }

        try {
            $uploadedFile = $request->file('file');

            $drive = new Drive(
                $google->clientFromAccount($account)
            );

            $baseName = pathinfo($request->filename, PATHINFO_FILENAME);

            $this->deleteOldFileByBaseName(
                $drive,
                $request->folder_id,
                $baseName
            );

            $metadata = new GoogleDriveFile([
                'name' => $request->filename,
                'parents' => [$request->folder_id],
            ]);

            $created = $drive->files->create($metadata, [
                'data' => file_get_contents($uploadedFile->getRealPath()),
                'mimeType' => $uploadedFile->getMimeType(),
                'uploadType' => 'multipart',
                'fields' => 'id,name,webViewLink,webContentLink,mimeType,size',
            ]);

            $permission = new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);

            $drive->permissions->create($created->id, $permission);

            $driveFile = DriveFile::create([
                'file_uid' => (string) Str::uuid(),
                'drive_account_id' => $account->id,
                'google_file_id' => $created->id,
                'name' => $created->name,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $created->mimeType ?? $uploadedFile->getMimeType(),
                'size' => $uploadedFile->getSize(),
                'source_app' => $request->source_app ?? 'sadarin',
                'folder' => $request->folder ?? 'upload-drive',
                'reference_id' => $request->reference_id,
            ]);

            $apiClient->update([
                'last_used_at' => now(),
            ]);

            $url = $created->webViewLink ?: 'https://drive.google.com/file/d/' . $created->id . '/view';

            return response()->json([
                'success' => true,
                'message' => 'File berhasil diupload ke Google Drive.',
                'data' => [
                    'file_id' => $driveFile->id,
                    'file_uid' => $driveFile->file_uid,
                    'google_file_id' => $created->id,
                    'name' => $created->name,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $created->mimeType ?? $uploadedFile->getMimeType(),
                    'size' => $uploadedFile->getSize(),
                    'url' => $url,
                    'drive_account' => $account->email,
                    'folder_id' => $request->folder_id,
                    'source_app' => $driveFile->source_app,
                    'folder' => $driveFile->folder,
                    'reference_id' => $driveFile->reference_id,
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
    public function uploadSPJ(Request $request, DriveAllocator $allocator, GoogleDriveService $google)
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
            'file' => ['required', 'file', 'max:2048000'],
            'group' => ['nullable', 'exists:drive_groups,slug'],
            'source_app' => ['nullable', 'string', 'max:100'],
            'folder' => ['nullable', 'string', 'max:150'],
            'reference_id' => ['nullable', 'string', 'max:150'],
            'jenis' => ['nullable', 'string', 'max:100'],
        ]);

        $groupSlug = $apiClient->group_slug ?: $request->group;

        if (!$groupSlug) {
            return response()->json([
                'success' => false,
                'message' => 'Group tujuan belum ditentukan.',
            ], 422);
        }

        $uploaded = $request->file('file');

        $account = $allocator->selectDrive(
            $uploaded->getSize(),
            $groupSlug
        );

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada akun Drive dengan storage cukup.',
            ], 422);
        }

        $drive = new Drive(
            $google->clientFromAccount($account)
        );

        $extension = $uploaded->getClientOriginalExtension();

        $fileName =
            ($request->source_app ?: 'arindrive') .
            '_' .
            ($request->jenis ?: 'file') .
            '_' .
            ($request->reference_id ?: 'ref') .
            '_' .
            now()->format('Ymd_His');

        if ($extension) {
            $fileName .= '.' . $extension;
        }

        $googleFile = new GoogleDriveFile();
        $googleFile->setName($fileName);

        $created = $drive->files->create($googleFile, [
            'data' => file_get_contents($uploaded->getRealPath()),
            'mimeType' => $uploaded->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id,name,mimeType,size',
        ]);

        $file = DriveFile::create([
            'file_uid' => (string) Str::uuid(),
            'drive_account_id' => $account->id,
            'google_file_id' => $created->id,
            'name' => $created->name,
            'original_name' => $uploaded->getClientOriginalName(),
            'mime_type' => $created->mimeType,
            'size' => $uploaded->getSize(),
            'source_app' => $request->source_app,
            'folder' => $request->folder,
            'reference_id' => $request->reference_id,
        ]);

        $google->syncStorage($account);

        $apiClient->update([
            'last_used_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File berhasil diupload.',
            'data' => [
                'file_id' => $file->id,
                'url' => route('files.show', $file->file_uid),
                'name' => $file->name,
                'original_name' => $file->original_name,
                'size' => $file->size,
                'mime_type' => $file->mime_type,
                'group' => $account->group?->slug,
                'drive_account' => $account->email,
                'google_file_id' => $file->google_file_id,
            ],
        ]);
    }
}