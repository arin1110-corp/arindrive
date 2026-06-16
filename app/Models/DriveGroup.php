<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveGroup extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    public function accounts()
    {
        return $this->hasMany(DriveAccount::class);
    }
}