<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\DriveGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiClientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'group_slug' => ['nullable', 'exists:drive_groups,slug'],
        ]);

        ApiClient::create([
            'name' => $request->name,
            'group_slug' => $request->group_slug,
            'token' => 'ard_' . Str::random(60),
            'is_active' => true,
        ]);

        return back()->with('success', 'API Key berhasil dibuat.');
    }

    public function toggle(ApiClient $client)
    {
        $client->update([
            'is_active' => !$client->is_active,
        ]);

        return back()->with('success', 'Status API berhasil diubah.');
    }

    public function destroy(ApiClient $client)
    {
        $client->delete();

        return back()->with('success', 'API Key berhasil dihapus.');
    }
}