<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ward_reports', function (Blueprint $table) {
            $table->foreignId('polling_station_id')->nullable()->after('ward_id')
                ->constrained('polling_stations')->nullOnDelete();
            $table->string('ec8a_serial_number', 100)->nullable()->after('polling_station_id');
            $table->decimal('turnout_rate', 5, 2)->nullable()->after('ec8a_serial_number');
            $table->unsignedInteger('registered_voter_count_snapshot')->nullable()->after('turnout_rate');
        });
    }

    public function down(): void
    {
        Schema::table('ward_reports', function (Blueprint $table) {
            $table->dropForeign(['polling_station_id']);
            $table->dropColumn([
                'polling_station_id',
                'ec8a_serial_number',
                'turnout_rate',
                'registered_voter_count_snapshot',
            ]);
        });
    }
};
