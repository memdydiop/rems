<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert 'available' and 'maintenance' to 'vacant'
        DB::table('units')
            ->whereIn('status', ['available', 'maintenance'])
            ->update(['status' => 'vacant']);

        // Safety check: ensure all units are either 'vacant' or 'occupied'
        // If there's any other value, default to 'vacant'
        DB::table('units')
            ->whereNotIn('status', ['vacant', 'occupied'])
            ->update(['status' => 'vacant']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No logical down migration for data normalization
    }
};
