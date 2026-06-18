<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drive_files', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('name');
            $table->string('source_app')->nullable()->after('mime_type');
            $table->string('folder')->nullable()->after('source_app');
            $table->string('reference_id')->nullable()->after('folder');
        });
    }

    public function down(): void
    {
        Schema::table('drive_files', function (Blueprint $table) {
            $table->dropColumn([
                'original_name',
                'source_app',
                'folder',
                'reference_id',
            ]);
        });
    }
};