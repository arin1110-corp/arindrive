<?php

namespace App\Services;

use App\Models\DriveAccount;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Oauth2;

class GoogleDriveService
{
    public function client(): Client
    {
        $client = new Client();

        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

        $client->addScope(Drive::DRIVE);
        $client->addScope(Oauth2::USERINFO_EMAIL);

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    public function clientFromAccount(DriveAccount $account): Client
    {
        $client = $this->client();

        $client->setAccessToken(json_decode($account->access_token, true));

        if ($client->isAccessTokenExpired() && $account->refresh_token) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($account->refresh_token);

            $oldToken = json_decode($account->access_token, true);
            $mergedToken = array_merge($oldToken ?? [], $newToken);

            $account->update([
                'access_token' => json_encode($mergedToken),
            ]);

            $client->setAccessToken($mergedToken);
        }

        return $client;
    }

    public function syncStorage(DriveAccount $account): void
    {
        $client = $this->clientFromAccount($account);
        $drive = new Drive($client);

        $about = $drive->about->get([
            'fields' => 'storageQuota',
        ]);

        $quota = $about->getStorageQuota();

        $account->update([
            'storage_limit' => $quota->getLimit() ?? 0,
            'storage_used' => $quota->getUsage() ?? 0,
        ]);
    }
}