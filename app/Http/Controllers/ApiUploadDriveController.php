<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\DriveAccount;
use App\Models\DriveFile;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile as GoogleDriveFile;
use Google\Service\Drive\Permission;

class ApiUploadDriveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | UPLOAD BIASA
    |--------------------------------------------------------------------------
    | JANGAN DIUBAH
    |--------------------------------------------------------------------------
    */

    public function upload(Request $request, GoogleDriveService $google)
    {
        $token = str_replace('Bearer ', '', $request->header('Authorization'));

        $apiClient = ApiClient::where('token', $token)->where('is_active', true)->first();

        if (!$apiClient) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'API token tidak valid.',
                ],
                401,
            );
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

        try {
            $account = $this->findAccountForFolder($request->folder_id, $request->drive_account_id, $google);

            if (!$account) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Tidak ditemukan akun Google Drive aktif yang memiliki akses ke folder tujuan.',
                    ],
                    422,
                );
            }

            $drive = new Drive($google->clientFromAccount($account));

            $uploadedFile = $request->file('file');

            $baseName = pathinfo($request->filename, PATHINFO_FILENAME);

            $this->deleteOldFileByBaseName($drive, $request->folder_id, $baseName);

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

            $url = 'https://drive.google.com/file/d/' . $created->id . '/view';

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

                    'drive_account_id' => $account->id,

                    'folder_id' => $request->folder_id,

                    'source_app' => $driveFile->source_app,

                    'folder' => $driveFile->folder,

                    'reference_id' => $driveFile->reference_id,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'success' => false,

                    'message' => 'Gagal upload ke Google Drive: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPLOAD SPJ
    |--------------------------------------------------------------------------
    | KHUSUS SPJ
    |--------------------------------------------------------------------------
    */

    public function uploadSPJ(Request $request, GoogleDriveService $google)
    {
        /*
    |--------------------------------------------------------------------------
    | API TOKEN
    |--------------------------------------------------------------------------
    */

        $token = str_replace('Bearer ', '', $request->header('Authorization'));

        $apiClient = ApiClient::where('token', $token)->where('is_active', true)->first();

        if (!$apiClient) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'API token tidak valid.',
                ],
                401,
            );
        }

        /*
    |--------------------------------------------------------------------------
    | VALIDASI
    |--------------------------------------------------------------------------
    */

        $request->validate([
            'file' => 'required|file|max:2048000',
            'folder_id' => 'required|string',
            'filename' => 'required|string|max:255',
            'drive_account_id' => 'nullable|integer',
            'source_app' => 'nullable|string|max:100',
            'folder' => 'nullable|string|max:150',
            'reference_id' => 'nullable|string|max:150',
        ]);

        \Log::info('=== SPJ ENDPOINT TEST ===', [
            'endpoint' => 'upload-drive-spj',
            'folder_id' => $request->folder_id,
            'filename' => $request->filename,
            'reference_id' => $request->reference_id,
            'drive_account_id' => $request->drive_account_id,
        ]);

        try {
            /*
        |--------------------------------------------------------------------------
        | SAMA PERSIS DENGAN UPLOAD BIASA
        |--------------------------------------------------------------------------
        */

            $account = $this->findAccountForFolder($request->folder_id, $request->drive_account_id, $google);

            if (!$account) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Tidak ditemukan akun Google Drive aktif yang memiliki akses ke folder tujuan.',
                    ],
                    422,
                );
            }

            /*
        |--------------------------------------------------------------------------
        | CLIENT GOOGLE DRIVE
        |--------------------------------------------------------------------------
        */

            $drive = new Drive($google->clientFromAccount($account));

            /*
        |--------------------------------------------------------------------------
        | FILE
        |--------------------------------------------------------------------------
        */

            $uploadedFile = $request->file('file');

            /*
        |--------------------------------------------------------------------------
        | HAPUS FILE SPJ LAMA
        |--------------------------------------------------------------------------
        |
        | Ini SATU-SATUNYA bagian khusus SPJ.
        |
        | File lama dicari melalui database DriveFile.
        | Akun lama diambil dari drive_account_id.
        |
        */

            if ($request->reference_id) {
                $oldFiles = DriveFile::where('reference_id', $request->reference_id)->get();

                foreach ($oldFiles as $oldFile) {
                    try {
                        $oldAccount = DriveAccount::where('id', $oldFile->drive_account_id)->where('is_active', true)->first();

                        if ($oldAccount) {
                            $oldDrive = new Drive($google->clientFromAccount($oldAccount));

                            $oldDrive->files->delete($oldFile->google_file_id);
                        }
                    } catch (\Throwable $e) {
                        /*
                    |------------------------------------------------------------------
                    | File lama tidak ditemukan / token lama bermasalah.
                    | Jangan menggagalkan upload file baru.
                    |------------------------------------------------------------------
                    */
                    }

                    /*
                |--------------------------------------------------------------------------
                | HAPUS RECORD DATABASE LAMA
                |--------------------------------------------------------------------------
                */

                    $oldFile->delete();
                }
            }

            /*
        |--------------------------------------------------------------------------
        | METADATA
        |--------------------------------------------------------------------------
        |
        | INI SAMA PERSIS DENGAN UPLOAD BIASA.
        |
        */

            $metadata = new GoogleDriveFile([
                'name' => $request->filename,

                'parents' => [$request->folder_id],
            ]);

            /*
        |--------------------------------------------------------------------------
        | UPLOAD
        |--------------------------------------------------------------------------
        |
        | SAMA PERSIS DENGAN UPLOAD BIASA.
        |
        */

            $created = $drive->files->create($metadata, [
                'data' => file_get_contents($uploadedFile->getRealPath()),

                'mimeType' => $uploadedFile->getMimeType(),

                'uploadType' => 'multipart',

                'fields' => 'id,name,webViewLink,webContentLink,mimeType,size',
            ]);

            /*
        |--------------------------------------------------------------------------
        | PUBLIC READ
        |--------------------------------------------------------------------------
        |
        | SAMA PERSIS DENGAN UPLOAD BIASA.
        |
        */

            $permission = new Permission([
                'type' => 'anyone',

                'role' => 'reader',
            ]);

            $drive->permissions->create($created->id, $permission);

            /*
        |--------------------------------------------------------------------------
        | SIMPAN DATABASE
        |--------------------------------------------------------------------------
        */

            $driveFile = DriveFile::create([
                'file_uid' => (string) Str::uuid(),

                'drive_account_id' => $account->id,

                'google_file_id' => $created->id,

                'name' => $created->name,

                'original_name' => $uploadedFile->getClientOriginalName(),

                'mime_type' => $created->mimeType ?? $uploadedFile->getMimeType(),

                'size' => $uploadedFile->getSize(),

                'source_app' => $request->source_app ?? 'saplarin',

                'folder' => $request->folder ?? 'spj',

                /*
                |--------------------------------------------------------------------------
                | INI YANG MEMBUAT SPJ BISA EDIT/HAPUS
                |--------------------------------------------------------------------------
                */

                'reference_id' => $request->reference_id,
            ]);

            /*
        |--------------------------------------------------------------------------
        | UPDATE API CLIENT
        |--------------------------------------------------------------------------
        */

            $apiClient->update([
                'last_used_at' => now(),
            ]);

            /*
        |--------------------------------------------------------------------------
        | URL
        |--------------------------------------------------------------------------
        */

            $url = 'https://drive.google.com/file/d/' . $created->id . '/view';

            /*
        |--------------------------------------------------------------------------
        | RESPONSE
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'success' => true,

                'message' => 'File SPJ berhasil diupload ke Google Drive.',

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

                    'drive_account_id' => $account->id,

                    'folder_id' => $request->folder_id,

                    'source_app' => $driveFile->source_app,

                    'folder' => $driveFile->folder,

                    'reference_id' => $driveFile->reference_id,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'success' => false,

                    'message' => 'Gagal upload SPJ ke Google Drive: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CARI AKUN SPJ
    |--------------------------------------------------------------------------
    |
    | BERBEDA dengan findAccountForFolder()
    |
    | Hanya dipakai uploadSPJ().
    |--------------------------------------------------------------------------
    */

    private function findSPJAccountForFolder(string $folderId, ?int $requestedAccountId, GoogleDriveService $google): ?DriveAccount
    {
        /*
        |--------------------------------------------------------------------------
        | 1. JIKA ACCOUNT ID DIKIRIM
        |--------------------------------------------------------------------------
        */

        if ($requestedAccountId) {
            $account = DriveAccount::where('id', $requestedAccountId)->where('is_active', true)->first();

            if (!$account) {
                return null;
            }

            if ($this->spjAccountCanWriteFolder($account, $folderId, $google)) {
                return $account;
            }

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. AMBIL SEMUA AKUN AKTIF
        |--------------------------------------------------------------------------
        */

        $accounts = DriveAccount::where('is_active', true)->get();

        /*
        |--------------------------------------------------------------------------
        | 3. PRIORITAS AKUN PEMILIK FOLDER
        |--------------------------------------------------------------------------
        */

        foreach ($accounts as $account) {
            try {
                $drive = new Drive($google->clientFromAccount($account));

                $folder = $drive->files->get($folderId, [
                    'fields' => 'id,name,mimeType,capabilities,owners(emailAddress)',
                ]);

                if ($folder->mimeType !== 'application/vnd.google-apps.folder') {
                    continue;
                }

                $owners = $folder->getOwners();

                if (!$owners) {
                    continue;
                }

                foreach ($owners as $owner) {
                    $ownerEmail = strtolower(trim($owner->getEmailAddress()));

                    $accountEmail = strtolower(trim($account->email));

                    if ($ownerEmail === $accountEmail) {
                        if ($this->spjAccountCanWriteFolder($account, $folderId, $google)) {
                            return $account;
                        }
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4. FALLBACK AKUN YANG BISA MENULIS
        |--------------------------------------------------------------------------
        */

        foreach ($accounts as $account) {
            if ($this->spjAccountCanWriteFolder($account, $folderId, $google)) {
                return $account;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | CEK AKUN SPJ BISA MENULIS KE FOLDER
    |--------------------------------------------------------------------------
    */

    private function spjAccountCanWriteFolder(DriveAccount $account, string $folderId, GoogleDriveService $google): bool
    {
        try {
            $drive = new Drive($google->clientFromAccount($account));

            $folder = $drive->files->get($folderId, [
                'fields' => 'id,name,mimeType,capabilities,owners(emailAddress)',
            ]);

            /*
            |--------------------------------------------------------------------------
            | HARUS FOLDER
            |--------------------------------------------------------------------------
            */

            if ($folder->mimeType !== 'application/vnd.google-apps.folder') {
                return false;
            }

            /*
            |--------------------------------------------------------------------------
            | CEK CAPABILITY
            |--------------------------------------------------------------------------
            */

            $capabilities = $folder->getCapabilities();

            if (!$capabilities) {
                return true;
            }

            if (method_exists($capabilities, 'getCanAddChildren')) {
                return (bool) $capabilities->getCanAddChildren();
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CARI AKUN YANG MEMILIKI AKSES KE FOLDER
    |--------------------------------------------------------------------------
    |
    | INI UNTUK UPLOAD BIASA.
    | TIDAK DIUBAH LOGIKANYA.
    |--------------------------------------------------------------------------
    */

    private function findAccountForFolder(string $folderId, ?int $requestedAccountId, GoogleDriveService $google): ?DriveAccount
    {
        if ($requestedAccountId) {
            $account = DriveAccount::where('id', $requestedAccountId)->where('is_active', true)->first();

            if (!$account) {
                return null;
            }

            if ($this->accountCanAccessFolder($account, $folderId, $google)) {
                return $account;
            }

            return null;
        }

        $accounts = DriveAccount::where('is_active', true)->orderBy('storage_used', 'asc')->get();

        foreach ($accounts as $account) {
            if ($this->accountCanAccessFolder($account, $folderId, $google)) {
                return $account;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | CEK AKUN BISA AKSES FOLDER
    |--------------------------------------------------------------------------
    */

    private function accountCanAccessFolder(DriveAccount $account, string $folderId, GoogleDriveService $google): bool
    {
        try {
            $drive = new Drive($google->clientFromAccount($account));

            $folder = $drive->files->get($folderId, [
                'fields' => 'id,name,mimeType,capabilities',
            ]);

            if ($folder->mimeType !== 'application/vnd.google-apps.folder') {
                return false;
            }

            $capabilities = $folder->getCapabilities();

            if (!$capabilities) {
                return false;
            }

            if (method_exists($capabilities, 'getCanAddChildren')) {
                return (bool) $capabilities->getCanAddChildren();
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE FILE LAMA BIASA
    |--------------------------------------------------------------------------
    */

    private function deleteOldFileByBaseName(Drive $drive, string $folderId, string $baseName): void
    {
        $safeBaseName = str_replace("'", "\\'", $baseName);

        $files = $drive->files->listFiles([
            'q' => "'{$folderId}' in parents
                    and trashed = false
                    and name contains '{$safeBaseName}'",

            'fields' => 'files(id,name)',
        ]);

        foreach ($files->files as $file) {
            try {
                $drive->files->delete($file->id);
            } catch (\Throwable $e) {
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE FILE SPJ BERDASARKAN REFERENCE
    |--------------------------------------------------------------------------
    |
    | PENTING:
    |
    | Tidak menggunakan akun aktif saat ini.
    |
    | Kita baca drive_account_id dari DriveFile,
    | sehingga file lama di Gmail lama tetap bisa dihapus.
    |--------------------------------------------------------------------------
    */

    private function deleteSPJOldFilesByReference(?string $referenceId, GoogleDriveService $google): void
    {
        if (!$referenceId) {
            return;
        }

        $oldFiles = DriveFile::where('reference_id', $referenceId)->get();

        foreach ($oldFiles as $old) {
            try {
                /*
                |--------------------------------------------------------------------------
                | CARI AKUN ASLI FILE
                |--------------------------------------------------------------------------
                */

                $account = DriveAccount::where('id', $old->drive_account_id)->where('is_active', true)->first();

                if ($account) {
                    $drive = new Drive($google->clientFromAccount($account));

                    try {
                        $drive->files->delete($old->google_file_id, [
                            'supportsAllDrives' => true,
                        ]);
                    } catch (\Throwable $e) {
                        /*
                        |--------------------------------------------------------------------------
                        | FILE MUNGKIN SUDAH TERHAPUS
                        |--------------------------------------------------------------------------
                        */
                    }
                }
            } catch (\Throwable $e) {
            }

            /*
            |--------------------------------------------------------------------------
            | HAPUS RECORD DATABASE
            |--------------------------------------------------------------------------
            */

            $old->delete();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE FILE LAMA BERDASARKAN REFERENCE
    |--------------------------------------------------------------------------
    |
    | Dipertahankan untuk kompatibilitas kode lama.
    |--------------------------------------------------------------------------
    */

    private function deleteOldFileByReference(Drive $drive, ?string $referenceId): void
    {
        if (!$referenceId) {
            return;
        }

        $oldFiles = DriveFile::where('reference_id', $referenceId)->get();

        foreach ($oldFiles as $old) {
            try {
                $drive->files->delete($old->google_file_id, [
                    'supportsAllDrives' => true,
                ]);
            } catch (\Throwable $e) {
            }

            $old->delete();
        }
    }
}