<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polling_unit_cluster_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('polling_unit_agent_id')
                ->constrained('polling_unit_agents')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_number', 20)->nullable();
            $table->string('nin', 11)->nullable();
            $table->string('voters_card_number', 20)->nullable();
            $table->string('arty_membership_reg_number', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('polling_unit_cluster_members');
    }
};
