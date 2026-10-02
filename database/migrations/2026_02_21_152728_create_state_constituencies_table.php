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
        Schema::create('state_constituencies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 20)->unique();
            $table->foreignId('state_id')->constrained('states');
            $table->foreignId('lga_id')->nullable()->constrained('lgas');
            $table->timestamps();

            $table->index(['state_id', 'lga_id']);
        });

        // Pivot table for state constituency to ward (many-to-many)
        Schema::create('state_constituency_ward', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_constituency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ward_id')->constrained()->cascadeOnDelete();
            $table->unique(['state_constituency_id', 'ward_id'], 'sc_ward_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_constituency_ward');
        Schema::dropIfExists('state_constituencies');
    }
};
