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
        // First, migrate existing rent amounts from units to active leases
        $units = DB::table('units')->get();
        foreach ($units as $unit) {
            DB::table('leases')
                ->where('unit_id', $unit->id)
                ->where('status', 'active')
                ->update(['rent_amount' => $unit->rent_amount]);
        }

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('rent_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->decimal('rent_amount', 10, 2)->default(0);
        });

        // Restore rent amounts from active leases back to units
        $units = DB::table('units')->get();
        foreach ($units as $unit) {
            $activeLease = DB::table('leases')
                ->where('unit_id', $unit->id)
                ->where('status', 'active')
                ->first();

            if ($activeLease) {
                DB::table('units')
                    ->where('id', $unit->id)
                    ->update(['rent_amount' => $activeLease->rent_amount]);
            }
        }
    }
};
