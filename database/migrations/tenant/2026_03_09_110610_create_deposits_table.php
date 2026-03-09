<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lease_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // security, advance
            $table->decimal('amount', 12, 2);
            $table->date('paid_at')->nullable();
            $table->decimal('returned_amount', 12, 2)->default(0);
            $table->date('returned_at')->nullable();
            $table->text('deductions')->nullable();
            $table->string('status')->default('pending'); // pending, held, partial_return, returned
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
