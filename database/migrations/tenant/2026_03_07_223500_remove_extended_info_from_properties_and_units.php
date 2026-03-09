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
            $table->dropColumn(['acd_number', 'tax_number', 'construction_year', 'latitude', 'longitude']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['orientation', 'lot_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('acd_number')->nullable();
            $table->string('tax_number')->nullable();
            $table->integer('construction_year')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
        });

        Schema::table('units', function (Blueprint $table) {
            $table->string('orientation')->nullable();
            $table->string('lot_number')->nullable();
        });
    }
};
