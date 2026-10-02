<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pu_party_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_report_id')->constrained('ward_reports')->cascadeOnDelete();
            $table->string('party_name', 50);
            $table->unsignedInteger('votes_scored')->default(0);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pu_party_results');
    }
};
