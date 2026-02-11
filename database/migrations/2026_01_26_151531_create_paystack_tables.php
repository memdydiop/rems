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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('paystack_code')->unique(); // PLN_xxx
            $table->integer('amount'); // In lowest denomination (kobo/cents)
            $table->string('currency')->default('NGN');
            $table->string('interval')->default('monthly'); // monthly, annually
            $table->integer('trial_period_days')->default(0);
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id'); // String because Tenant ID is UUID/String
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('paystack_id')->nullable(); // SUB_xxx
            $table->string('paystack_code')->nullable(); // Subscription code
            $table->string('email_token')->nullable();
            $table->string('status')->default('pending'); // active, non-renewing, attention, completed, cancelled
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paystack_tables');
    }
};
