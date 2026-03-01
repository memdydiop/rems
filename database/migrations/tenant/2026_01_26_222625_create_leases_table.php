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
        Schema::create('leases', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignUuid('renter_id')->constrained('renters')->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date')->nullable(); // Nullable for periodic leases (month-to-month)

            $table->decimal('rent_amount', 12, 2);
            $table->decimal('deposit_amount', 12, 2)->default(0);

            $table->string('status')->default('pending'); // pending, active, expired, terminated

            $table->json('documents')->nullable(); // Array of file paths

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};
