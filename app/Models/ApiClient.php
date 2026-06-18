<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    protected $fillable = [
        'name',
        'token',
        'group_slug',
        'is_active',
        'last_used_at',
    ];
}