<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DriveGroup;

class DriveGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DriveGroup::create([
            'name' => 'Kantor',
            'slug' => 'kantor',
        ]);

        DriveGroup::create([
            'name' => 'Bisnis',
            'slug' => 'bisnis',
        ]);

        DriveGroup::create([
            'name' => 'Client',
            'slug' => 'client',
        ]);

        DriveGroup::create([
            'name' => 'Pribadi',
            'slug' => 'pribadi',
        ]);
    }
}