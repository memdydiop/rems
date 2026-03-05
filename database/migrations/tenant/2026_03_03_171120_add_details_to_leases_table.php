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
        Schema::table('leases', function (Blueprint $table) {
            $table->date('notice_date')->nullable()->after('end_date');
            $table->date('move_out_date')->nullable()->after('notice_date');
            $table->decimal('charges_amount', 12, 2)->default(0)->after('rent_amount');
            $table->string('lease_type')->nullable()->after('status');
            $table->text('notes')->nullable()->after('documents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn([
                'notice_date',
                'move_out_date',
                'charges_amount',
                'lease_type',
                'notes',
            ]);
        });
    }
};
