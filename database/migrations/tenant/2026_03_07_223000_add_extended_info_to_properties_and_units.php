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
        Schema::table('properties', function (Blueprint $table) {
            $table->string('acd_number')->nullable()->after('type');
            $table->string('tax_number')->nullable()->after('acd_number');
            $table->integer('construction_year')->nullable()->after('tax_number');
            $table->decimal('latitude', 10, 8)->nullable()->after('construction_year');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->string('orientation')->nullable()->after('type');
            $table->string('lot_number')->nullable()->after('orientation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['acd_number', 'tax_number', 'construction_year', 'latitude', 'longitude']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['orientation', 'lot_number']);
        });
    }
};
