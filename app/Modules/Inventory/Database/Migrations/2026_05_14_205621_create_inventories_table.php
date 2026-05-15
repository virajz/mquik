<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // stock_entries is the central write ledger for all stock movements.
        // Every module (Purchase, SalesInvoice, Challan, Consumption, etc.)
        // inserts rows here. The Inventory module reads/projects from this table.
        Schema::create('stock_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_id')->constrained('spares')->restrictOnDelete();

            // Movement type — drives FIFO layer logic and reporting buckets
            $table->string('entry_type', 30);
            // purchase | purchase_return | sale | sale_return | challan_out
            // challan_return | ipo_issue | ipo_return | consumption | adjustment | opening

            // Source document — polymorphic so any module can reference its own record
            $table->string('source_type', 60)->nullable();  // e.g. App\Modules\Purchase\Models\Purchase
            $table->unsignedBigInteger('source_id')->nullable();

            // Signed qty: positive = stock IN, negative = stock OUT
            $table->decimal('qty', 12, 2);

            // Rate at time of movement (for FIFO cost layer tracking)
            $table->decimal('rate_per_unit', 12, 2)->default(0);

            // Optional location override (multi-rack/multi-bin future)
            $table->string('location', 64)->nullable();

            $table->dateTime('moved_at');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['spare_id', 'moved_at']);
            $table->index(['entry_type', 'moved_at']);
            $table->index(['source_type', 'source_id']);
            $table->index('moved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_entries');
    }
};
