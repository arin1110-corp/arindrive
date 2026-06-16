<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveFile extends Model
{
    protected $fillable = [
        'drive_account_id',
        'google_file_id',
        'name',
        'mime_type',
        'size',
    ];

    public function driveAccount()
    {
        return $this->belongsTo(DriveAccount::class);
    }
}