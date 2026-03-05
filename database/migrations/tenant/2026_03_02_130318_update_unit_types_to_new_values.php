<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Mapper les anciens types d'unité vers les nouveaux
        $mappings = [
            'apartment' => 'f3', // Appartement générique => F3 par défaut
        ];

        foreach ($mappings as $old => $new) {
            DB::table('units')->where('type', $old)->update(['type' => $new]);
        }
    }

    public function down(): void
    {
        // Non réversible
    }
};
