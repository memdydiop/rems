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
        Schema::table('units', function (Blueprint $table) {
            $table->decimal('rent_amount', 10, 2)->nullable()->after('type');
            $table->decimal('surface_area', 8, 2)->nullable()->after('rent_amount');
            $table->text('notes')->nullable()->after('surface_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['rent_amount', 'surface_area', 'notes']);
        });
    }
};
