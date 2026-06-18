<?php

namespace App\Http\Controllers;

use App\Models\DriveGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DriveGroupController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        DriveGroup::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => true,
        ]);

        return back()->with('success', 'Grup berhasil ditambahkan.');
    }

    public function update(Request $request, DriveGroup $group)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $group->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Grup berhasil diperbarui.');
    }

    public function destroy(DriveGroup $group)
    {
        if ($group->accounts()->count() > 0) {
            return back()->with('error', 'Grup masih memiliki akun Drive.');
        }

        $group->delete();

        return back()->with('success', 'Grup berhasil dihapus.');
    }
}