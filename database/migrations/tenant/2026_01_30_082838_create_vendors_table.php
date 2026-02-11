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
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Standard Tenancy FK if using single DB or just for reference
            // But since we are likely inside the tenant DB context (multi-db), 
            // we don't strictly need tenant_id IF we are using one DB per tenant.
            // checking config... Stancl Tenancy usually adds tenant_id if single-db.
            // Let's assume standard multi-db for now OR standard Schema helper handles it? 
            // Wait, standard Blueprint doesn't add tenant_id auto. 
            // If we are Multi-DB: No tenant_id needed.
            // If we are Single-DB: tenant_id needed.
            // Based on previous files (Tenant routes), it seems to be domain-based tenancy.
            // SAFE BET: Add it if using single-db mode, but usually safer to just add standard fields.
            // I'll stick to standard fields for now.

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('service_type')->nullable(); // Plumbing, Electrical, etc.
            $table->text('address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
