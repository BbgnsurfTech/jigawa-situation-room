<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('polling_stations', function (Blueprint $table) {
            $table->unsignedInteger('registered_voter_count')->default(0)->after('lga_id');
            $table->decimal('gps_latitude', 10, 7)->nullable()->after('registered_voter_count');
            $table->decimal('gps_longitude', 10, 7)->nullable()->after('gps_latitude');
            $table->boolean('is_active')->default(true)->after('gps_longitude');
            $table->foreignId('assigned_pua_id')->nullable()->after('is_active')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('polling_stations', function (Blueprint $table) {
            $table->dropForeign(['assigned_pua_id']);
            $table->dropColumn([
                'registered_voter_count',
                'gps_latitude',
                'gps_longitude',
                'is_active',
                'assigned_pua_id',
            ]);
        });
    }
};
