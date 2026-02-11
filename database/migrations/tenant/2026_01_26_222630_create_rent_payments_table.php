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
        Schema::create('rent_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('lease_id')->constrained('leases')->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->date('paid_at');

            $table->string('method'); // bank_transfer, cash, card, etc.
            $table->string('status')->default('completed'); // completed, pending, failed

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_payments');
    }
};
