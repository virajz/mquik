<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spares', function (Blueprint $table) {
            $table->foreignId('part_type_id')->nullable()->after('spare_brand_id')->constrained('part_types')->nullOnDelete();
            $table->foreignId('rack_id')->nullable()->after('location')->constrained('racks')->nullOnDelete();
        });

        Schema::create('spare_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_id')->constrained('spares')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['spare_id', 'vendor_id']);
            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spare_vendor');

        Schema::table('spares', function (Blueprint $table) {
            $table->dropConstrainedForeignId('part_type_id');
            $table->dropConstrainedForeignId('rack_id');
        });
    }
};
