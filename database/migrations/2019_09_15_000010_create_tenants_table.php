<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('company')->nullable();
            $table->string('plan')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('tenancy_db_name')->nullable(); // Standard for Tenancy
            $table->string('paystack_customer_code')->nullable();
            $table->string('paystack_auth_code')->nullable();

            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
