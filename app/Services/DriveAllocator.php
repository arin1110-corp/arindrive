<?php

namespace App\Services;

use App\Models\DriveAccount;

class DriveAllocator
{
    public function selectDrive(int $fileSize, ?string $groupSlug = null): ?DriveAccount
    {
        return DriveAccount::query()
            ->with('group')
            ->where('is_active', true)
            ->when($groupSlug, function ($query) use ($groupSlug) {
                $query->whereHas('group', function ($q) use ($groupSlug) {
                    $q->where('slug', $groupSlug);
                });
            })
            ->get()
            ->filter(function ($account) use ($fileSize) {
                if (!$account->storage_limit) {
                    return false;
                }

                return $account->free_storage >= $fileSize;
            })
            ->sortByDesc('free_storage')
            ->first();
    }
}