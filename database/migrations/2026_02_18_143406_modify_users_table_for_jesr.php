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
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->after('id');
            $table->string('phone', 20)->unique()->after('full_name');
            $table->enum('role', ['super_admin', 'supervisor', 'ward_agent'])->default('ward_agent')->after('phone');
            $table->unsignedBigInteger('lga_id')->nullable()->after('role');
            $table->unsignedBigInteger('ward_id')->nullable()->after('lga_id');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('ward_id');
            $table->string('party_affiliation')->nullable()->after('gender');
            $table->string('device_id')->nullable()->after('party_affiliation');
            $table->timestamp('last_login_at')->nullable()->after('device_id');
            $table->boolean('is_active')->default(true)->after('last_login_at');
            $table->boolean('mfa_enabled')->default(false)->after('is_active');
            $table->string('mfa_secret')->nullable()->after('mfa_enabled');

            // Make existing columns nullable
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();

            // Foreign key constraints
            $table->foreign('lga_id')->references('id')->on('lgas');
            $table->foreign('ward_id')->references('id')->on('wards');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['lga_id']);
            $table->dropForeign(['ward_id']);

            // Drop columns
            $table->dropColumn([
                'full_name',
                'phone',
                'role',
                'lga_id',
                'ward_id',
                'gender',
                'party_affiliation',
                'device_id',
                'last_login_at',
                'is_active',
                'mfa_enabled',
                'mfa_secret',
            ]);

            // Revert nullable changes
            $table->string('name')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
