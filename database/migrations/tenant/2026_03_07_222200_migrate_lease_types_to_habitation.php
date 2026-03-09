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
        DB::table('leases')->whereIn('lease_type', ['vide', 'meuble'])->update(['lease_type' => 'habitation']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No simple way to reverse without knowing which ones were 'vide' and which ones were 'meuble'.
        // We'll leave them as 'habitation' or default back to 'vide'.
        DB::table('leases')->where('lease_type', 'habitation')->update(['lease_type' => 'vide']);
    }
};
