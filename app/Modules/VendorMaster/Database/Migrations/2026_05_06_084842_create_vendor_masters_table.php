<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            // Identity
            $table->string('vendor_code', 30)->unique();
            $table->string('name');
            $table->foreignId('vendor_type_id')->nullable()->constrained('vendor_types')->restrictOnDelete();
            // Contact
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();
            $table->string('email')->nullable();
            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->char('pincode', 6)->nullable();
            // KYC
            $table->char('pan', 10)->nullable()->unique();
            $table->string('gstin', 15)->nullable()->unique();
            // Banking
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->char('ifsc', 11)->nullable();
            $table->string('account_no', 30)->nullable();
            $table->string('account_holder')->nullable();
            // Credit
            $table->unsignedInteger('credit_days')->default(0);
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->string('payment_terms')->nullable(); // free text e.g. "Net 30 / Advance"
            // Status
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('phone');
            $table->index(['vendor_type_id', 'name']);
            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
