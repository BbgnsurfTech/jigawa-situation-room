<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polling_unit_agents', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('surname', 100);
            $table->string('polling_unit');
            $table->string('polling_unit_number', 50);
            $table->foreignId('ward_id')->constrained('wards');
            $table->foreignId('lga_id')->constrained('lgas');
            $table->string('state', 100);
            $table->string('nin', 11)->unique();
            $table->string('voters_card_number', 20)->unique();
            $table->string('bvn', 11)->nullable();
            $table->string('account_number', 10)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->json('leadership_roles')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_unit_agents');
    }
};
