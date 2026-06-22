<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('gst_type_id')->nullable()->after('gstin')->constrained('gst_types')->nullOnDelete();
        });

        Schema::create('vendor_service_specialist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('service_specialist_id')->constrained('service_specialists')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['vendor_id', 'service_specialist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_service_specialist');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('gst_type_id');
        });
    }
};
