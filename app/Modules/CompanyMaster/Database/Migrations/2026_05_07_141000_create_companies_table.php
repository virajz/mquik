<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');                                            // "Mquik Auto Services Pvt Ltd"
            $table->string('trade_name');                                            // "Mquik Workshop"
            $table->string('code', 20)->nullable();                                  // short code
            // Tax registrations
            $table->string('gstin', 15)->nullable()->unique();
            $table->char('pan', 10)->nullable();
            $table->string('cin', 21)->nullable();                                   // CIN for Pvt Ltd
            // Address
            $table->text('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->char('pincode', 6)->nullable();
            // Contact
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            // Branding
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            // Defaults
            $table->text('invoice_footer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('legal_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
