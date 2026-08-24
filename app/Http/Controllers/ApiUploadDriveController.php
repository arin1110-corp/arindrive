<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\DriveAccount;
use App\Models\DriveFile;
use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile as GoogleDriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiUploadDriveController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | UPLOAD BIASA
    |--------------------------------------------------------------------------
    */

    public function upload(
        Request $request,
        GoogleDriveService $google
    ) {
        /*
        |--------------------------------------------------------------------------
        | API TOKEN
        |--------------------------------------------------------------------------
        */

        $token = str_replace(
            'Bearer ',
            '',
            $request->header('Authorization')
        );

        $apiClient = ApiClient::where(
            'token',
            $token
        )
            ->where(
                'is_active',
                true
            )
            ->first();

        if (!$apiClient) {

            return response()->json([
                'success' => false,
                'message' => 'API token tidak valid.',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'file' =>
            'required|file|max:2048000',

            'folder_id' =>
            'required|string',

            'filename' =>
            'required|string|max:255',

            'drive_account_id' =>
            'nullable|integer',

            'source_app' =>
            'nullable|string|max:100',

            'folder' =>
            'nullable|string|max:150',

            'reference_id' =>
            'nullable|string|max:150',

        ]);


        try {

            /*
            |--------------------------------------------------------------------------
            | PILIH AKUN GOOGLE YANG BISA AKSES FOLDER
            |--------------------------------------------------------------------------
            */

            $account = $this->findAccountForFolder(
                $request->folder_id,
                $request->drive_account_id,
                $google
            );


            if (!$account) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Tidak ditemukan akun Google Drive aktif yang memiliki akses ke folder tujuan.',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | CLIENT GOOGLE DRIVE
            |--------------------------------------------------------------------------
            */

            $drive = new Drive(
                $google->clientFromAccount(
                    $account
                )
            );


            /*
            |--------------------------------------------------------------------------
            | FILE
            |--------------------------------------------------------------------------
            */

            $uploadedFile =
                $request->file('file');


            /*
            |--------------------------------------------------------------------------
            | NAMA DASAR FILE
            |--------------------------------------------------------------------------
            */

            $baseName = pathinfo(
                $request->filename,
                PATHINFO_FILENAME
            );


            /*
            |--------------------------------------------------------------------------
            | HAPUS FILE LAMA
            |--------------------------------------------------------------------------
            */

            $this->deleteOldFileByBaseName(
                $drive,
                $request->folder_id,
                $baseName
            );


            /*
            |--------------------------------------------------------------------------
            | METADATA
            |--------------------------------------------------------------------------
            */

            $metadata =
                new GoogleDriveFile([

                    'name' =>
                    $request->filename,

                    'parents' => [
                        $request->folder_id
                    ],

                ]);


            /*
            |--------------------------------------------------------------------------
            | UPLOAD
            |--------------------------------------------------------------------------
            */

            $created =
                $drive->files->create(
                    $metadata,
                    [

                        'data' =>
                        file_get_contents(
                            $uploadedFile->getRealPath()
                        ),

                        'mimeType' =>
                        $uploadedFile->getMimeType(),

                        'uploadType' =>
                        'multipart',

                        'fields' =>
                        'id,name,webViewLink,webContentLink,mimeType,size',

                    ]
                );


            /*
            |--------------------------------------------------------------------------
            | PUBLIC READ
            |--------------------------------------------------------------------------
            */

            $permission =
                new Permission([

                    'type' =>
                    'anyone',

                    'role' =>
                    'reader',

                ]);


            $drive->permissions->create(
                $created->id,
                $permission
            );


            /*
            |--------------------------------------------------------------------------
            | SIMPAN DATABASE
            |--------------------------------------------------------------------------
            */

            $driveFile =
                DriveFile::create([

                    'file_uid' =>
                    (string) Str::uuid(),

                    'drive_account_id' =>
                    $account->id,

                    'google_file_id' =>
                    $created->id,

                    'name' =>
                    $created->name,

                    'original_name' =>
                    $uploadedFile
                        ->getClientOriginalName(),

                    'mime_type' =>
                    $created->mimeType
                        ??
                        $uploadedFile
                        ->getMimeType(),

                    'size' =>
                    $uploadedFile->getSize(),

                    'source_app' =>
                    $request->source_app
                        ??
                        'sadarin',

                    'folder' =>
                    $request->folder
                        ??
                        'upload-drive',

                    'reference_id' =>
                    $request->reference_id,

                ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE API CLIENT
            |--------------------------------------------------------------------------
            */

            $apiClient->update([

                'last_used_at' =>
                now(),

            ]);


            /*
            |--------------------------------------------------------------------------
            | URL
            |--------------------------------------------------------------------------
            */

            $url =
                $created->webViewLink
                ?:
                'https://drive.google.com/file/d/'
                .
                $created->id
                .
                '/view';


            /*
            |--------------------------------------------------------------------------
            | RESPONSE
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' =>
                true,

                'message' =>
                'File berhasil diupload ke Google Drive.',

                'data' => [

                    'file_id' =>
                    $driveFile->id,

                    'file_uid' =>
                    $driveFile->file_uid,

                    'google_file_id' =>
                    $created->id,

                    'name' =>
                    $created->name,

                    'original_name' =>
                    $uploadedFile
                        ->getClientOriginalName(),

                    'mime_type' =>
                    $created->mimeType
                        ??
                        $uploadedFile
                        ->getMimeType(),

                    'size' =>
                    $uploadedFile->getSize(),

                    'url' =>
                    $url,

                    'drive_account' =>
                    $account->email,

                    'drive_account_id' =>
                    $account->id,

                    'folder_id' =>
                    $request->folder_id,

                    'source_app' =>
                    $driveFile->source_app,

                    'folder' =>
                    $driveFile->folder,

                    'reference_id' =>
                    $driveFile->reference_id,

                ],

            ]);
        } catch (\Throwable $e) {

            return response()->json([

                'success' =>
                false,

                'message' =>
                'Gagal upload ke Google Drive: '
                    .
                    $e->getMessage(),

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPLOAD SPJ
    |--------------------------------------------------------------------------
    */

    public function uploadSPJ(
        Request $request,
        GoogleDriveService $google
    ) {

        /*
        |--------------------------------------------------------------------------
        | API TOKEN
        |--------------------------------------------------------------------------
        */

        $token = str_replace(
            'Bearer ',
            '',
            $request->header('Authorization')
        );


        $apiClient = ApiClient::where(
            'token',
            $token
        )
            ->where(
                'is_active',
                true
            )
            ->first();


        if (!$apiClient) {

            return response()->json([
                'success' => false,
                'message' => 'API token tidak valid.',
            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'file' =>
            'required|file|max:2048000',

            'folder_id' =>
            'required|string',

            'filename' =>
            'required|string|max:255',

            'drive_account_id' =>
            'nullable|integer',

            'source_app' =>
            'nullable|string|max:100',

            'folder' =>
            'nullable|string|max:150',

            'reference_id' =>
            'nullable|string|max:150',

        ]);


        try {

            /*
            |--------------------------------------------------------------------------
            | PILIH AKUN YANG BISA AKSES FOLDER
            |--------------------------------------------------------------------------
            */

            $account = $this->findAccountForFolder(
                $request->folder_id,
                $request->drive_account_id,
                $google
            );


            if (!$account) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'Tidak ditemukan akun Google Drive aktif yang memiliki akses ke folder tujuan.',
                ], 422);
            }


            /*
            |--------------------------------------------------------------------------
            | CLIENT
            |--------------------------------------------------------------------------
            */

            $drive = new Drive(
                $google->clientFromAccount(
                    $account
                )
            );


            /*
            |--------------------------------------------------------------------------
            | FILE
            |--------------------------------------------------------------------------
            */

            $uploadedFile =
                $request->file('file');


            /*
            |--------------------------------------------------------------------------
            | HAPUS FILE LAMA BERDASARKAN REFERENCE
            |--------------------------------------------------------------------------
            */

            $this->deleteOldFileByReference(
                $drive,
                $request->reference_id
            );


            /*
            |--------------------------------------------------------------------------
            | METADATA
            |--------------------------------------------------------------------------
            */

            $metadata =
                new GoogleDriveFile([

                    'name' =>
                    $request->filename,

                    'parents' => [
                        $request->folder_id
                    ],

                ]);


            /*
            |--------------------------------------------------------------------------
            | UPLOAD
            |--------------------------------------------------------------------------
            */

            $created =
                $drive->files->create(
                    $metadata,
                    [

                        'data' =>
                        file_get_contents(
                            $uploadedFile->getRealPath()
                        ),

                        'mimeType' =>
                        $uploadedFile->getMimeType(),

                        'uploadType' =>
                        'multipart',

                        'fields' =>
                        'id,name,webViewLink,webContentLink,mimeType,size',

                    ]
                );


            /*
            |--------------------------------------------------------------------------
            | PUBLIC READ
            |--------------------------------------------------------------------------
            */

            $permission =
                new Permission([

                    'type' =>
                    'anyone',

                    'role' =>
                    'reader',

                ]);


            $drive->permissions->create(
                $created->id,
                $permission
            );


            /*
            |--------------------------------------------------------------------------
            | DATABASE
            |--------------------------------------------------------------------------
            */

            $driveFile =
                DriveFile::create([

                    'file_uid' =>
                    (string) Str::uuid(),

                    'drive_account_id' =>
                    $account->id,

                    'google_file_id' =>
                    $created->id,

                    'name' =>
                    $created->name,

                    'original_name' =>
                    $uploadedFile
                        ->getClientOriginalName(),

                    'mime_type' =>
                    $created->mimeType
                        ??
                        $uploadedFile
                        ->getMimeType(),

                    'size' =>
                    $uploadedFile->getSize(),

                    'source_app' =>
                    $request->source_app
                        ??
                        'sadarin',

                    'folder' =>
                    $request->folder
                        ??
                        'upload-drive',

                    'reference_id' =>
                    $request->reference_id,

                ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE API CLIENT
            |--------------------------------------------------------------------------
            */

            $apiClient->update([

                'last_used_at' =>
                now(),

            ]);


            /*
            |--------------------------------------------------------------------------
            | URL
            |--------------------------------------------------------------------------
            */

            $url =
                $created->webViewLink
                ?:
                'https://drive.google.com/file/d/'
                .
                $created->id
                .
                '/view';


            /*
            |--------------------------------------------------------------------------
            | RESPONSE
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' =>
                true,

                'message' =>
                'File berhasil diupload ke Google Drive.',

                'data' => [

                    'file_id' =>
                    $driveFile->id,

                    'file_uid' =>
                    $driveFile->file_uid,

                    'google_file_id' =>
                    $created->id,

                    'name' =>
                    $created->name,

                    'original_name' =>
                    $uploadedFile
                        ->getClientOriginalName(),

                    'mime_type' =>
                    $created->mimeType
                        ??
                        $uploadedFile
                        ->getMimeType(),

                    'size' =>
                    $uploadedFile->getSize(),

                    'url' =>
                    $url,

                    'drive_account' =>
                    $account->email,

                    'drive_account_id' =>
                    $account->id,

                    'folder_id' =>
                    $request->folder_id,

                    'source_app' =>
                    $driveFile->source_app,

                    'folder' =>
                    $driveFile->folder,

                    'reference_id' =>
                    $driveFile->reference_id,

                ],

            ]);
        } catch (\Throwable $e) {

            return response()->json([

                'success' =>
                false,

                'message' =>
                'Gagal upload ke Google Drive: '
                    .
                    $e->getMessage(),

            ], 500);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CARI AKUN YANG MEMILIKI AKSES KE FOLDER
    |--------------------------------------------------------------------------
    */

    private function findAccountForFolder(
        string $folderId,
        ?int $requestedAccountId,
        GoogleDriveService $google
    ): ?DriveAccount {

        /*
        |--------------------------------------------------------------------------
        | JIKA AKUN DIPAKSA DARI REQUEST
        |--------------------------------------------------------------------------
        */

        if ($requestedAccountId) {

            $account =
                DriveAccount::where(
                    'id',
                    $requestedAccountId
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();


            if (!$account) {
                return null;
            }


            /*
            |----------------------------------------------------------------------
            | VALIDASI AKSES FOLDER
            |----------------------------------------------------------------------
            */

            if (
                $this->accountCanAccessFolder(
                    $account,
                    $folderId,
                    $google
                )
            ) {

                return $account;
            }


            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | SEMUA AKUN AKTIF
        |--------------------------------------------------------------------------
        */

        $accounts =
            DriveAccount::where(
                'is_active',
                true
            )
            ->orderBy(
                'storage_used',
                'asc'
            )
            ->get();


        /*
        |--------------------------------------------------------------------------
        | CARI YANG BISA AKSES FOLDER
        |--------------------------------------------------------------------------
        */

        foreach ($accounts as $account) {

            if (
                $this->accountCanAccessFolder(
                    $account,
                    $folderId,
                    $google
                )
            ) {

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

    private function accountCanAccessFolder(
        DriveAccount $account,
        string $folderId,
        GoogleDriveService $google
    ): bool {

        try {

            $drive =
                new Drive(
                    $google->clientFromAccount(
                        $account
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | CEK FOLDER
            |--------------------------------------------------------------------------
            |
            | Jika akun tidak memiliki akses,
            | Google akan melempar exception.
            |
            */

            $folder =
                $drive->files->get(
                    $folderId,
                    [
                        'fields' =>
                        'id,name,mimeType,capabilities',
                    ]
                );


            /*
            |--------------------------------------------------------------------------
            | HARUS FOLDER
            |--------------------------------------------------------------------------
            */

            if (
                $folder->mimeType
                !==
                'application/vnd.google-apps.folder'
            ) {

                return false;
            }


            /*
            |--------------------------------------------------------------------------
            | CEK KEMAMPUAN MENAMBAHKAN FILE
            |--------------------------------------------------------------------------
            */

            $capabilities =
                $folder->getCapabilities();


            if (!$capabilities) {
                return false;
            }


            /*
            |--------------------------------------------------------------------------
            | Google Drive API
            |--------------------------------------------------------------------------
            |
            | canAddChildren = akun bisa menambahkan
            | file/folder ke dalam folder.
            |
            */

            if (
                method_exists(
                    $capabilities,
                    'getCanAddChildren'
                )
            ) {

                return (bool)
                $capabilities
                    ->getCanAddChildren();
            }


            /*
            |--------------------------------------------------------------------------
            | FALLBACK
            |--------------------------------------------------------------------------
            |
            | Kalau property capabilities tidak tersedia,
            | akses GET folder berarti minimal folder terlihat.
            |
            | Kita kembalikan true agar proses upload
            | yang menentukan permission sebenarnya.
            |
            */

            return true;
        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | AKUN TIDAK PUNYA AKSES
            |--------------------------------------------------------------------------
            */

            return false;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE FILE LAMA BERDASARKAN NAMA
    |--------------------------------------------------------------------------
    */

    private function deleteOldFileByBaseName(
        Drive $drive,
        string $folderId,
        string $baseName
    ): void {

        $safeBaseName =
            str_replace(
                "'",
                "\\'",
                $baseName
            );


        $files =
            $drive->files->listFiles([

                'q' =>
                "'{$folderId}' in parents
                    and trashed = false
                    and name contains '{$safeBaseName}'",

                'fields' =>
                'files(id,name)',

            ]);


        foreach (
            $files->files
            as $file
        ) {

            try {

                $drive->files->delete(
                    $file->id
                );
            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | ABAIKAN FILE YANG SUDAH TIDAK ADA
                |--------------------------------------------------------------------------
                */
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE FILE LAMA BERDASARKAN REFERENCE
    |--------------------------------------------------------------------------
    */

    private function deleteOldFileByReference(
        Drive $drive,
        ?string $referenceId
    ): void {

        if (!$referenceId) {
            return;
        }


        $oldFiles =
            DriveFile::where(
                'reference_id',
                $referenceId
            )
            ->get();


        foreach (
            $oldFiles
            as $old
        ) {

            try {

                $drive->files->delete(
                    $old->google_file_id
                );
            } catch (\Throwable $e) {

                /*
                |--------------------------------------------------------------------------
                | ABAIKAN JIKA FILE SUDAH TIDAK ADA
                |--------------------------------------------------------------------------
                */
            }


            $old->delete();
        }
    }
}