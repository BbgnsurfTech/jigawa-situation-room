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
        Schema::table('candidates', function (Blueprint $table) {
            // Add state reference
            $table->foreignId('state_id')->nullable()->after('post_type')->constrained('states');

            // Add constituency references
            $table->foreignId('senatorial_district_id')->nullable()->constrained('senatorial_districts');
            $table->foreignId('federal_constituency_id')->nullable()->constrained('federal_constituencies');
            $table->foreignId('state_constituency_id')->nullable()->constrained('state_constituencies');

            $table->index('state_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['senatorial_district_id']);
            $table->dropForeign(['federal_constituency_id']);
            $table->dropForeign(['state_constituency_id']);
            $table->dropIndex(['state_id']);
            $table->dropColumn([
                'state_id',
                'senatorial_district_id',
                'federal_constituency_id',
                'state_constituency_id'
            ]);
        });
    }
};
