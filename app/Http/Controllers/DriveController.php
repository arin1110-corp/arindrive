<?php

namespace App\Http\Controllers;

use App\Models\DriveAccount;
use App\Models\DriveFile;
use App\Models\DriveGroup;
use App\Services\DriveAllocator;
use App\Services\GoogleDriveService;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile as GoogleDriveFile;
use Illuminate\Http\Request;

class DriveController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'groups' => \App\Models\DriveGroup::with('accounts')->where('is_active', true)->orderBy('name')->get(),
            'accounts' => \App\Models\DriveAccount::with('group')->latest()->get(),
            'files' => \App\Models\DriveFile::with('driveAccount.group')->latest()->get(),
            'apiClients' => \App\Models\ApiClient::latest()->get(),
        ]);
    }

    public function syncStorage(GoogleDriveService $google)
    {
        $accounts = DriveAccount::where('is_active', true)->get();

        foreach ($accounts as $account) {
            $google->syncStorage($account);
        }

        return redirect()->route('dashboard')->with('success', 'Storage semua akun berhasil disinkronkan.');
    }

    public function upload(Request $request, DriveAllocator $allocator, GoogleDriveService $google)
    {
        $request->validate([
            'drive_group_slug' => ['required', 'exists:drive_groups,slug'],
            'file' => ['required', 'file', 'max:2048000'],
        ]);

        $uploaded = $request->file('file');

        $account = $allocator->selectDrive($uploaded->getSize(), $request->drive_group_slug);

        if (!$account) {
            return back()->with('error', 'Tidak ada akun Google Drive pada grup ini yang memiliki storage cukup.');
        }

        $client = $google->clientFromAccount($account);
        $drive = new Drive($client);

        $extension = $uploaded->getClientOriginalExtension();

        $fileName = 'testarindrive_' . now()->format('Ymd_His');

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

        DriveFile::create([
            'drive_account_id' => $account->id,
            'google_file_id' => $created->id,
            'name' => $created->name,
            'mime_type' => $created->mimeType,
            'size' => $uploaded->getSize(),
        ]);

        $google->syncStorage($account);

        return redirect()
            ->route('dashboard')
            ->with('success', 'File berhasil diupload ke grup ' . $account->group?->name . ' melalui akun: ' . $account->email);
    }
    public function deleteAccount($id)
    {
        $account = \App\Models\DriveAccount::findOrFail($id);

        if ($account->files()->count() > 0) {
            return back()->with('error', 'Akun masih memiliki metadata file. Nonaktifkan saja agar file lama tetap aman.');
        }

        $account->delete();

        return back()->with('success', 'Akun berhasil dihapus dari ArinDrive. File di Google Drive tidak dihapus.');
    }

    public function toggleAccount($id)
    {
        $account = \App\Models\DriveAccount::findOrFail($id);

        $account->update([
            'is_active' => !$account->is_active,
        ]);

        return back()->with('success', 'Status akun berhasil diubah.');
    }
}