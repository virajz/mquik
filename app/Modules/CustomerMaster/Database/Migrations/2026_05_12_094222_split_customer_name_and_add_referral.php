<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - Split `name` into `first_name`, `middle_name`, `last_name`.
 *   Corporates with multi-word company names dump the whole string into `first_name`.
 *   Heuristic for existing rows:
 *     1 word   → first_name
 *     2 words  → first + last
 *     3 words  → first + middle + last
 *     4+ words → all in first_name (assume company)
 *
 * - Add `referred_by_customer_id` (self-FK, nullable) to track which existing customer
 *   referred this one. Used for word-of-mouth attribution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->foreignId('referred_by_customer_id')
                ->nullable()
                ->after('business_type_id')
                ->constrained('customers')
                ->nullOnDelete();
        });

        // Backfill from existing `name`.
        $rows = DB::table('customers')->select('id', 'name')->get();
        foreach ($rows as $row) {
            [$first, $middle, $last] = $this->splitName((string) $row->name);
            DB::table('customers')->where('id', $row->id)->update([
                'first_name' => $first,
                'middle_name' => $middle,
                'last_name' => $last,
            ]);
        }

        Schema::table('customers', function (Blueprint $table) {
            // SQLite refuses to drop a column referenced by ANY index. The original
            // customers migration created three indexes touching `name`; later
            // migrations rebuilt one with business_type_id. Drop them all before
            // dropping the column.
            $table->dropIndex(['name']);
            $table->dropIndex(['business_type_id', 'name']);
            $table->dropIndex(['is_active', 'name']);
            $table->dropColumn('name');
            $table->index('first_name');
            $table->index('last_name');
            $table->index(['business_type_id', 'first_name']);
            $table->index(['is_active', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['first_name']);
            $table->dropIndex(['last_name']);
            $table->string('name')->nullable();
        });

        $rows = DB::table('customers')->select('id', 'first_name', 'middle_name', 'last_name')->get();
        foreach ($rows as $row) {
            $name = trim(implode(' ', array_filter([$row->first_name, $row->middle_name, $row->last_name])));
            DB::table('customers')->where('id', $row->id)->update(['name' => $name]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['referred_by_customer_id']);
            $table->dropColumn(['first_name', 'middle_name', 'last_name', 'referred_by_customer_id']);
            $table->index('name');
        });
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string} [first, middle, last]
     */
    protected function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name) ?? '');
        if ($name === '') {
            return [null, null, null];
        }

        $parts = explode(' ', $name);
        $count = count($parts);

        return match (true) {
            $count === 1 => [$parts[0], null, null],
            $count === 2 => [$parts[0], null, $parts[1]],
            $count === 3 => [$parts[0], $parts[1], $parts[2]],
            default => [$name, null, null], // 4+ → assume company
        };
    }
};
