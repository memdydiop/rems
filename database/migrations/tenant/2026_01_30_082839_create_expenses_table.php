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
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('category')->default('maintenance'); // maintenance, utilities, tax, insurance, other

            // Relationships (Nullable because an expense might be general)
            $table->foreignUuid('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('vendor_id')->nullable()->constrained()->nullOnDelete();

            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
