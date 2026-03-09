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
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->renameColumn('period', 'period_start');
        });

        // Actually, let's be more explicit to avoid issues with rename and direct adds in the same closure.
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->date('period_end')->nullable()->after('period_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rent_payments', function (Blueprint $table) {
            $table->dropColumn('period_end');
            $table->renameColumn('period_start', 'period');
        });
    }
};
