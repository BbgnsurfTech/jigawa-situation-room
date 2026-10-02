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
            $table->string('mfa_otp', 6)->nullable()->after('mfa_secret');
            $table->timestamp('mfa_otp_expires_at')->nullable()->after('mfa_otp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mfa_otp', 'mfa_otp_expires_at']);
        });
    }
};
