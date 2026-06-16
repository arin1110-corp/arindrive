<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drive_accounts', function (Blueprint $table) {
            $table->foreignId('drive_group_id')->nullable()->after('id')->constrained('drive_groups')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drive_accounts', function (Blueprint $table) {
            //
        });
    }
};