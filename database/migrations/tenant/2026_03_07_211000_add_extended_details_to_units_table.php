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
            $table->integer('rooms_count')->nullable()->after('surface_area');
            $table->integer('bathrooms_count')->nullable()->after('rooms_count');
            $table->integer('floor_number')->nullable()->after('bathrooms_count');
            $table->string('electricity_meter_number')->nullable()->after('floor_number');
            $table->string('water_meter_number')->nullable()->after('electricity_meter_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn([
                'rooms_count',
                'bathrooms_count',
                'floor_number',
                'electricity_meter_number',
                'water_meter_number',
            ]);
        });
    }
};
