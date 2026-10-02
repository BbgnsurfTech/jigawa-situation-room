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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('party_id')->constrained('parties')->onDelete('cascade');
            $table->enum('post_type', ['governorship', 'senate', 'house_of_reps', 'state_assembly']);
            $table->foreignId('lga_id')->nullable()->constrained('lgas');
            $table->foreignId('ward_id')->nullable()->constrained('wards');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
