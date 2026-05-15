<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // barcode_labels stores generated codes so every scan resolves to a spare.
        // One spare can have multiple barcode aliases (OEM + aftermarket + internal).
        Schema::create('barcode_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_id')->constrained('spares')->cascadeOnDelete();

            $table->string('barcode', 128)->unique();
            $table->string('barcode_type', 16)->default('code128'); // code128 | qr | ean13
            $table->string('label_size', 16)->default('50x25');     // mm WxH
            $table->unsignedSmallInteger('copies')->default(1);
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['spare_id', 'is_primary']);
            $table->index('barcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_labels');
    }
};
