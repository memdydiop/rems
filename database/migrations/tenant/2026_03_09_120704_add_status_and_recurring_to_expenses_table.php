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
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('status')->default('paid'); // paid, pending
            $table->boolean('is_recurring')->default(false);
            $table->string('frequency')->nullable(); // monthly, quarterly, yearly
            $table->date('next_due_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_recurring', 'frequency', 'next_due_date']);
        });
    }
};
