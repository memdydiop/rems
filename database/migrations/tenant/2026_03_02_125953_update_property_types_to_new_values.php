<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $mappings = [
            'apartment' => 'residential_building',
            'studio' => 'residential_building',
            'duplex_triplex' => 'residential_building',
            'multi_family' => 'residential_building',
            'office' => 'commercial_building',
            'retail' => 'commercial_building',
            'restaurant' => 'commercial_building',
            'industrial_space' => 'industrial_complex',
            'parking' => 'commercial_building',
        ];

        foreach ($mappings as $old => $new) {
            DB::table('properties')->where('type', $old)->update(['type' => $new]);
        }
    }

    public function down(): void
    {
        // Non réversible - les anciennes valeurs exactes ne peuvent pas être retrouvées.
    }
};
