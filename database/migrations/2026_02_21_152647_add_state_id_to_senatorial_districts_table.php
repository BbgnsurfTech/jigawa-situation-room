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
        Schema::table('senatorial_districts', function (Blueprint $table) {
            $table->foreignId('state_id')->nullable()->after('name')->constrained('states');
            $table->string('code', 20)->nullable()->after('state_id');
            $table->index('state_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('senatorial_districts', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropIndex(['state_id']);
            $table->dropColumn(['state_id', 'code']);
        });
    }
};
