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
        Schema::table('ward_reports', function (Blueprint $table) {
            $table->text('flag_reason')->nullable()->after('remarks');
            $table->text('supervisor_note')->nullable()->after('flag_reason');
            $table->text('clarification_request')->nullable()->after('supervisor_note');
            $table->timestamp('clarification_sent_at')->nullable()->after('clarification_request');
        });
    }

    public function down(): void
    {
        Schema::table('ward_reports', function (Blueprint $table) {
            $table->dropColumn(['flag_reason', 'supervisor_note', 'clarification_request', 'clarification_sent_at']);
        });
    }
};
