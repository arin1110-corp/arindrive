<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveFile extends Model
{
    protected $fillable = [
        'file_uid',
        'drive_account_id',
        'google_file_id',
        'name',
        'original_name',
        'mime_type',
        'size',
        'source_app',
        'folder',
        'reference_id',
    ];

    public function driveAccount()
    {
        return $this->belongsTo(DriveAccount::class);
    }
}