<?php

namespace App\Http\Controllers;

use App\Models\DriveAccount;
use App\Models\DriveGroup;
use App\Services\GoogleDriveService;
use Google\Service\Oauth2;
use Illuminate\Http\Request;

class GoogleController extends Controller
{
    public function connectForm()
    {
        return view('google-connect', [
            'groups' => DriveGroup::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function redirect(Request $request, GoogleDriveService $google)
    {
        $request->validate([
            'drive_group_id' => ['required', 'exists:drive_groups,id'],
        ]);

        session([
            'drive_group_id' => $request->drive_group_id,
        ]);

        return redirect()->away($google->client()->createAuthUrl());
    }

    public function callback(Request $request, GoogleDriveService $google)
    {
        if (!$request->has('code')) {
            return redirect()->route('dashboard')->with('error', 'Login Google dibatalkan.');
        }

        $reconnectId = session('reconnect_account_id');
        $driveGroupId = session('drive_group_id');

        if (!$reconnectId && !$driveGroupId) {
            return redirect()->route('google.connect')->with('error', 'Pilih grup Drive terlebih dahulu.');
        }

        $client = $google->client();

        $token = $client->fetchAccessTokenWithAuthCode($request->code);

        if (isset($token['error'])) {
            return redirect()
                ->route('dashboard')
                ->with('error', $token['error_description'] ?? 'Gagal mengambil token Google.');
        }

        $client->setAccessToken($token);

        $oauth = new Oauth2($client);
        $info = $oauth->userinfo->get();

        if ($reconnectId) {
            $account = DriveAccount::findOrFail($reconnectId);

            $refreshToken = $token['refresh_token'] ?? $account->refresh_token;

            $account->update([
                'google_id' => $info->id,
                'email' => $info->email,
                'access_token' => json_encode($token),
                'refresh_token' => $refreshToken,
                'is_active' => true,
            ]);

            session()->forget('reconnect_account_id');

            $google->syncStorage($account);

            return redirect()->route('dashboard')->with('success', 'Akun Google Drive berhasil dihubungkan ulang.');
        }

        $oldAccount = DriveAccount::where('email', $info->email)->first();
        $refreshToken = $token['refresh_token'] ?? $oldAccount?->refresh_token;

        $account = DriveAccount::updateOrCreate(
            ['email' => $info->email],
            [
                'drive_group_id' => $driveGroupId,
                'google_id' => $info->id,
                'access_token' => json_encode($token),
                'refresh_token' => $refreshToken,
                'is_active' => true,
            ],
        );

        session()->forget('drive_group_id');

        $google->syncStorage($account);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Akun Google Drive berhasil ditambahkan ke grup: ' . $account->group?->name);
    }
    public function reconnect($id, GoogleDriveService $google)
    {
        $account = DriveAccount::findOrFail($id);

        session([
            'reconnect_account_id' => $account->id,
        ]);

        return redirect()->away($google->client()->createAuthUrl());
    }
}