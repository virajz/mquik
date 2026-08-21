<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which departments a requested repair belongs to.
 *
 * The repair list is shared across the whole workshop, so a bodyshop job card
 * offers tyre and detailing jobs alongside its own — hundreds of irrelevant
 * options to scroll past. A repair can legitimately belong to more than one
 * department (a wash is both DETAILING and SERVICE), hence a pivot rather than
 * a single column.
 *
 * A repair with no department stays visible everywhere: that is the pre-existing
 * state of every row today, and hiding them all would empty the picker.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requested_repair_workshop_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_repair_id')->constrained('requested_repairs')->cascadeOnDelete();
            $table->foreignId('workshop_department_id')->constrained('workshop_departments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['requested_repair_id', 'workshop_department_id'], 'rr_wd_unique');
            $table->index('workshop_department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requested_repair_workshop_department');
    }
};
