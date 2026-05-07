<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                              // e.g. "Mquik Satellite Branch"
            $table->string('code', 20)->unique();                                // e.g. "SAT", "AHM01"
            $table->boolean('is_head_office')->default(false);
            // Address
            $table->text('address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('regions')->nullOnDelete();   // points to a kind=city region
            $table->foreignId('state_id')->nullable()->constrained('regions')->nullOnDelete();  // points to a kind=state region
            $table->char('pincode', 6)->nullable();
            // Contact
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            // Tax
            $table->string('gstin', 15)->nullable()->unique();                   // workshop branch's own GSTIN (each branch is a separate registration in India)
            // Status
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index(['is_active', 'name']);
            $table->index('city_id');
            $table->index('state_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
