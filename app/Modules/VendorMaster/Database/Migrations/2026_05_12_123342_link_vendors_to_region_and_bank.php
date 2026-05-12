<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace free-text address (city/state/pincode) with a single region FK
 * pulled from RegionMaster, and free-text bank_name with a bank FK pulled
 * from BankMaster. Backfills best-effort:
 *   - region: pincode → kind=pincode region; falls back to city → kind=city
 *   - bank:   firstOrCreate by name (existing strings get a fresh bank row)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('address')->constrained('regions')->nullOnDelete();
            $table->foreignId('bank_id')->nullable()->after('gstin')->constrained('banks')->nullOnDelete();
        });

        // Backfill region from existing pincode / city.
        $rows = DB::table('vendors')->select('id', 'city', 'pincode')->get();
        foreach ($rows as $row) {
            $regionId = null;
            if ($row->pincode) {
                $regionId = DB::table('regions')
                    ->where('kind', 'pincode')
                    ->where('name', $row->pincode)
                    ->value('id');
            }
            if (! $regionId && $row->city) {
                $regionId = DB::table('regions')
                    ->where('kind', 'city')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($row->city)])
                    ->value('id');
            }
            if ($regionId) {
                DB::table('vendors')->where('id', $row->id)->update(['region_id' => $regionId]);
            }
        }

        // Backfill bank: firstOrCreate so existing free-text strings survive as
        // proper Bank rows (rather than being lost or skipped).
        $bankNames = DB::table('vendors')
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->pluck('bank_name')
            ->unique();

        foreach ($bankNames as $name) {
            DB::table('banks')->insertOrIgnore([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $bankIdByName = DB::table('banks')->pluck('id', 'name');

        DB::table('vendors')
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use ($bankIdByName) {
                foreach ($chunk as $row) {
                    $bankId = $bankIdByName[$row->bank_name] ?? null;
                    if ($bankId) {
                        DB::table('vendors')->where('id', $row->id)->update(['bank_id' => $bankId]);
                    }
                }
            });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['city', 'state', 'pincode', 'bank_name']);
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->char('pincode', 6)->nullable();
            $table->string('bank_name')->nullable();
        });

        // Restore best-effort: region's pincode/city/state names from the chain.
        $rows = DB::table('vendors')
            ->leftJoin('regions as r', 'r.id', '=', 'vendors.region_id')
            ->leftJoin('banks as b', 'b.id', '=', 'vendors.bank_id')
            ->select('vendors.id', 'r.kind as region_kind', 'r.name as region_name', 'r.parent_id', 'b.name as bank_name')
            ->get();

        foreach ($rows as $row) {
            $update = [];
            if ($row->bank_name) {
                $update['bank_name'] = $row->bank_name;
            }
            if ($row->region_kind === 'pincode') {
                $update['pincode'] = $row->region_name;
            } elseif ($row->region_kind === 'city') {
                $update['city'] = $row->region_name;
            } elseif ($row->region_kind === 'state') {
                $update['state'] = $row->region_name;
            }
            if (! empty($update)) {
                DB::table('vendors')->where('id', $row->id)->update($update);
            }
        }

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['bank_id']);
            $table->dropColumn(['region_id', 'bank_id']);
        });
    }
};
