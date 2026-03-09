<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('renters', 'clients');

        Schema::table('leases', function (Blueprint $table) {
            $table->renameColumn('renter_id', 'client_id');
        });

        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->renameColumn('renter_id', 'client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->renameColumn('client_id', 'renter_id');
        });

        Schema::table('leases', function (Blueprint $table) {
            $table->renameColumn('client_id', 'renter_id');
        });

        Schema::rename('clients', 'renters');
    }
};
