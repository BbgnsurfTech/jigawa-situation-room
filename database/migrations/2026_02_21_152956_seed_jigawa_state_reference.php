<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Link existing Jigawa data (LGAs, senatorial districts, users) to the Jigawa state.
     * This is a data migration that runs after zones and states have been seeded.
     */
    public function up(): void
    {
        // Find Jigawa state
        $jigawa = DB::table('states')->where('code', 'JIG')->first();

        if (!$jigawa) {
            // If no states exist yet (fresh migration), skip - seeders will handle it
            return;
        }

        // Update all existing LGAs to belong to Jigawa
        DB::table('lgas')
            ->whereNull('state_id')
            ->update(['state_id' => $jigawa->id]);

        // Update all senatorial districts to belong to Jigawa
        DB::table('senatorial_districts')
            ->whereNull('state_id')
            ->update(['state_id' => $jigawa->id]);

        // Update existing users to belong to Jigawa (those with lga_id but no state_id)
        DB::table('users')
            ->whereNotNull('lga_id')
            ->whereNull('state_id')
            ->update(['state_id' => $jigawa->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set state_id back to null for all affected records
        DB::table('lgas')->update(['state_id' => null]);
        DB::table('senatorial_districts')->update(['state_id' => null]);
        DB::table('users')->update(['state_id' => null]);
    }
};
