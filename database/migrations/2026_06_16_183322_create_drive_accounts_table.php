<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drive_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('google_id')->nullable();
            $table->string('email')->unique();
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->unsignedBigInteger('storage_limit')->nullable();
            $table->unsignedBigInteger('storage_used')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_accounts');
    }
};