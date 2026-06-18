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
    public function upload(Request $request, DriveAllocator $allocator, GoogleDriveService $google)
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