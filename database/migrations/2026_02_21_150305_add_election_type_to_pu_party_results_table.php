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
        Schema::table('pu_party_results', function (Blueprint $table) {
            $table->string('election_type', 50)->nullable()->after('party_name');
            $table->index('election_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pu_party_results', function (Blueprint $table) {
            $table->dropIndex(['election_type']);
            $table->dropColumn('election_type');
        });
    }
};
