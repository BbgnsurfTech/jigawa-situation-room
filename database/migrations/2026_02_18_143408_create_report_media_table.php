<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('ward_reports')->onDelete('cascade');
            $table->enum('media_type', ['photo', 'video', 'audio']);
            $table->string('storage_path');
            $table->string('thumbnail_path')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->enum('upload_status', ['pending', 'uploaded', 'failed'])->default('pending');
            $table->text('chain_of_custody_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_media');
    }
};
