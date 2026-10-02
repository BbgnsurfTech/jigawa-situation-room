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
        Schema::create('ward_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_user_id')->constrained('users');
            $table->foreignId('lga_id')->constrained('lgas');
            $table->foreignId('ward_id')->constrained('wards');
            $table->foreignId('party_agent_id')->nullable()->constrained('users');
            $table->string('inec_agent_name')->nullable();
            $table->unsignedInteger('counted_voter_papers')->default(0);
            $table->boolean('security_present')->default(false);
            $table->json('security_types')->nullable();
            $table->time('voting_closed_time')->nullable();
            $table->unsignedInteger('number_cast_votes')->default(0);
            $table->unsignedInteger('voting_papers_left')->default(0);
            $table->unsignedInteger('valid_ballot_papers')->default(0);
            $table->unsignedInteger('invalid_ballot_papers')->default(0);
            $table->text('remarks')->nullable();
            $table->enum('status', ['draft', 'submitted', 'verified', 'flagged', 'escalated'])->default('draft');
            $table->enum('submitted_via', ['web', 'android', 'ios', 'sms'])->default('web');
            $table->string('device_id')->nullable();
            $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('synced');
            $table->timestamp('submitted_at_device')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ward_reports');
    }
};
