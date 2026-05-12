<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Customers can have multiple addresses (Home, Office, Workshop, etc.).
 * Address geography (city/state/area/pincode) is anchored to a region row,
 * letting us pick from the existing state→city→area→pincode hierarchy
 * instead of free-typing.
 *
 * Backfills any existing customer's address/city/pincode into a single
 * primary row, then drops those columns from `customers`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('label', 50)->nullable();
            $table->text('address_line')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'is_primary']);
        });

        // Backfill: each customer with any address data → one primary row.
        // Best-effort region resolution: try pincode first, then city by name.
        $customers = DB::table('customers')
            ->select('id', 'address', 'city', 'pincode')
            ->where(function ($q) {
                $q->whereNotNull('address')
                    ->orWhereNotNull('city')
                    ->orWhereNotNull('pincode');
            })
            ->get();

        foreach ($customers as $c) {
            $regionId = null;

            if ($c->pincode) {
                $regionId = DB::table('regions')
                    ->where('kind', 'pincode')
                    ->where('name', $c->pincode)
                    ->value('id');
            }

            if (! $regionId && $c->city) {
                $regionId = DB::table('regions')
                    ->where('kind', 'city')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($c->city)])
                    ->value('id');
            }

            DB::table('customer_addresses')->insert([
                'customer_id' => $c->id,
                'label' => null,
                'address_line' => $c->address,
                'region_id' => $regionId,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['address', 'city', 'pincode']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->char('pincode', 6)->nullable();
        });

        // Roll the primary address (if any) back into the customer row.
        $primaries = DB::table('customer_addresses')
            ->select('customer_addresses.customer_id', 'customer_addresses.address_line', 'r.name as region_name', 'r.kind as region_kind')
            ->leftJoin('regions as r', 'r.id', '=', 'customer_addresses.region_id')
            ->where('customer_addresses.is_primary', true)
            ->get();

        foreach ($primaries as $p) {
            DB::table('customers')->where('id', $p->customer_id)->update([
                'address' => $p->address_line,
                'city' => $p->region_kind === 'city' ? $p->region_name : null,
                'pincode' => $p->region_kind === 'pincode' ? $p->region_name : null,
            ]);
        }

        Schema::dropIfExists('customer_addresses');
    }
};
