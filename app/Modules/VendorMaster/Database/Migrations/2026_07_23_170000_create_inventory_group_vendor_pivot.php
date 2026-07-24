<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which inventory groups a vendor supplies — "who do we buy brake parts from".
 *
 * Groups are hierarchical (40 parents, ~1,000 sub-groups) and a vendor may be
 * pinned at either level, so no level restriction is enforced here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_group_vendor', function (Blueprint $table) {
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('inventory_group_id')->constrained('inventory_groups')->cascadeOnDelete();
            $table->primary(['vendor_id', 'inventory_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_group_vendor');
    }
};
