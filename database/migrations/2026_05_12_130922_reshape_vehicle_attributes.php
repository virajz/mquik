<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vehicle data lives at the variant level, not the model level:
 *   - Move `fuel_type` from vehicle_models → vehicle_variants (a single Hyundai
 *     Creta has petrol AND diesel variants).
 *   - Add `year` to vehicle_variants (model-year is a variant attribute).
 * Also remove insurance/PUC tracking from CustomerVehicleMaster — those will
 * live in dedicated insurance/PUC modules later.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add new columns on variants.
        Schema::table('vehicle_variants', function (Blueprint $table) {
            $table->string('fuel_type', 20)->nullable()->after('engine_cc');
            $table->unsignedSmallInteger('year')->nullable()->after('fuel_type');

            $table->index('fuel_type');
            $table->index('year');
        });

        // Backfill variants' fuel_type from their parent model.
        DB::table('vehicle_models')
            ->whereNotNull('fuel_type')
            ->orderBy('id')
            ->chunkById(500, function ($models) {
                foreach ($models as $model) {
                    DB::table('vehicle_variants')
                        ->where('model_id', $model->id)
                        ->whereNull('fuel_type')
                        ->update(['fuel_type' => $model->fuel_type]);
                }
            });

        // Drop fuel_type (and its index) from vehicle_models.
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropIndex(['fuel_type']);
            $table->dropColumn('fuel_type');
        });

        // Drop insurance + PUC tracking from customer_vehicles. Indexes first
        // because SQLite refuses to drop a column referenced by an index.
        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->dropIndex(['insurance_expiry']);
            $table->dropIndex(['puc_expiry']);
            $table->dropColumn(['insurance_expiry', 'puc_expiry']);
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->string('fuel_type', 20)->nullable()->after('vehicle_segment_id');
            $table->index('fuel_type');
        });

        // Restore model.fuel_type by majority-voting across that model's variants.
        $rows = DB::table('vehicle_variants')
            ->whereNotNull('fuel_type')
            ->select('model_id', 'fuel_type', DB::raw('COUNT(*) as c'))
            ->groupBy('model_id', 'fuel_type')
            ->orderBy('model_id')
            ->orderByDesc('c')
            ->get()
            ->groupBy('model_id');

        foreach ($rows as $modelId => $variantRows) {
            DB::table('vehicle_models')
                ->where('id', $modelId)
                ->update(['fuel_type' => $variantRows->first()->fuel_type]);
        }

        Schema::table('vehicle_variants', function (Blueprint $table) {
            $table->dropIndex(['fuel_type']);
            $table->dropIndex(['year']);
            $table->dropColumn(['fuel_type', 'year']);
        });

        Schema::table('customer_vehicles', function (Blueprint $table) {
            $table->date('insurance_expiry')->nullable();
            $table->date('puc_expiry')->nullable();
            $table->index('insurance_expiry');
            $table->index('puc_expiry');
        });
    }
};
