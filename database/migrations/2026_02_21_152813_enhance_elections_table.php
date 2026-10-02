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
        Schema::table('elections', function (Blueprint $table) {
            // Add scope column for election level
            $table->string('scope', 30)->default('state')->after('type');

            // Add state and constituency references
            $table->foreignId('state_id')->nullable()->after('scope')->constrained('states');
            $table->foreignId('senatorial_district_id')->nullable()->constrained('senatorial_districts');
            $table->foreignId('federal_constituency_id')->nullable()->constrained('federal_constituencies');
            $table->foreignId('state_constituency_id')->nullable()->constrained('state_constituencies');

            $table->index(['scope', 'state_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['senatorial_district_id']);
            $table->dropForeign(['federal_constituency_id']);
            $table->dropForeign(['state_constituency_id']);
            $table->dropIndex(['scope', 'state_id']);
            $table->dropColumn([
                'scope',
                'state_id',
                'senatorial_district_id',
                'federal_constituency_id',
                'state_constituency_id'
            ]);
        });
    }
};
