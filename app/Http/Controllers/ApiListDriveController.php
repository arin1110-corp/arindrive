<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\DriveAccount;
use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Illuminate\Http\Request;

class ApiListDriveController extends Controller
{
    public function list(Request $request, GoogleDriveService $google)
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
            'folder_id' => 'required|string',
            'drive_account_id' => 'nullable|integer',
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | CARI ACCOUNT YANG PUNYA AKSES
            |--------------------------------------------------------------------------
            */

            $account = $this->findAccountForFolder($request->folder_id, $request->drive_account_id, $google);

            if (!$account) {
                return response()->json(
                    [
                        'success' => false,
                        'message' => 'Tidak ditemukan akun Google Drive yang memiliki akses ke folder.',
                    ],
                    422,
                );
            }

            /*
            |--------------------------------------------------------------------------
            | GOOGLE DRIVE CLIENT
            |--------------------------------------------------------------------------
            */

            $drive = new Drive($google->clientFromAccount($account));

            /*
            |--------------------------------------------------------------------------
            | LIST FILE DALAM FOLDER
            |--------------------------------------------------------------------------
            */

            $files = [];

            $pageToken = null;

            do {
                $params = [
                    'q' =>
                        "'" .
                        $request->folder_id .
                        "' in parents
                        and trashed = false",

                    'fields' => 'nextPageToken,files(id,name,mimeType,size,webViewLink,webContentLink,createdTime,modifiedTime)',

                    'pageSize' => 1000,

                    'orderBy' => 'name',
                ];

                if ($pageToken) {
                    $params['pageToken'] = $pageToken;
                }

                $response = $drive->files->listFiles($params);

                foreach ($response->getFiles() as $file) {
                    $files[] = [
                        'google_file_id' => $file->getId(),
                        'name' => $file->getName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'web_view_link' => $file->getWebViewLink(),
                        'web_content_link' => $file->getWebContentLink(),
                        'created_time' => $file->getCreatedTime(),
                        'modified_time' => $file->getModifiedTime(),
                    ];
                }

                $pageToken = $response->getNextPageToken();
            } while ($pageToken);

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
            | RESPONSE
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'File berhasil diambil dari Google Drive.',
                'data' => [
                    'folder_id' => $request->folder_id,
                    'drive_account_id' => $account->id,
                    'drive_account' => $account->email,
                    'total' => count($files),
                    'files' => $files,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Gagal mengambil file dari Google Drive: ' . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CARI AKUN YANG MEMILIKI AKSES KE FOLDER
    |--------------------------------------------------------------------------
    */

    private function findAccountForFolder(string $folderId, ?int $requestedAccountId, GoogleDriveService $google): ?DriveAccount
    {
        /*
        |--------------------------------------------------------------------------
        | JIKA ACCOUNT ID DIKIRIM
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | CARI DARI SEMUA ACCOUNT AKTIF
        |--------------------------------------------------------------------------
        */

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
    | CEK AKSES FOLDER
    |--------------------------------------------------------------------------
    */

    private function accountCanAccessFolder(DriveAccount $account, string $folderId, GoogleDriveService $google): bool
    {
        try {
            $drive = new Drive($google->clientFromAccount($account));

            $folder = $drive->files->get($folderId, [
                'fields' => 'id,name,mimeType,capabilities',
            ]);

            if ($folder->getMimeType() !== 'application/vnd.google-apps.folder') {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}