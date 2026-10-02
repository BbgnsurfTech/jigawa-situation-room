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
        Schema::create('federal_constituencies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 20)->unique();
            $table->foreignId('state_id')->constrained('states');
            $table->timestamps();

            $table->index('state_id');
        });

        // Pivot table for federal constituency to LGA (many-to-many)
        Schema::create('federal_constituency_lga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('federal_constituency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lga_id')->constrained()->cascadeOnDelete();
            $table->unique(['federal_constituency_id', 'lga_id'], 'fc_lga_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('federal_constituency_lga');
        Schema::dropIfExists('federal_constituencies');
    }
};
