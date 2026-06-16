<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveAccount extends Model
{
    protected $fillable = [
        'drive_group_id',
        'google_id',
        'email',
        'access_token',
        'refresh_token',
        'storage_limit',
        'storage_used',
        'is_active',
    ];

    public function group()
    {
        return $this->belongsTo(DriveGroup::class, 'drive_group_id');
    }

    public function files()
    {
        return $this->hasMany(DriveFile::class);
    }

    public function getFreeStorageAttribute()
    {
        return max(($this->storage_limit ?? 0) - ($this->storage_used ?? 0), 0);
    }
}