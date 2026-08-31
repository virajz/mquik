<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The two halves of the physical checklist that had no home yet: what was
 * explained to the customer and what they decided, and who signed the sheet off
 * inside the workshop.
 *
 * Also gives inspection groups and items an explicit running order. The
 * checklist is walked in a physical sequence — bonnet, then wheels, then
 * interior — and alphabetical order fights that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_inspections', function (Blueprint $table) {
            // Customer explanation & approval.
            $table->boolean('explained_on_lift')->default(false)->after('summary_notes');
            $table->boolean('media_shared')->default(false)->after('explained_on_lift');
            $table->boolean('questions_answered')->default(false)->after('media_shared');
            $table->string('customer_approval', 20)->nullable()->after('questions_answered');
            $table->timestamp('customer_approval_at')->nullable()->after('customer_approval');

            // Internal control: each row is a person and the moment they signed.
            $table->foreignId('technician_signed_by_id')->nullable()->after('customer_approval_at')
                ->constrained('employees')->nullOnDelete();
            $table->timestamp('technician_signed_at')->nullable()->after('technician_signed_by_id');
            $table->foreignId('supervisor_signed_by_id')->nullable()->after('technician_signed_at')
                ->constrained('employees')->nullOnDelete();
            $table->timestamp('supervisor_signed_at')->nullable()->after('supervisor_signed_by_id');
            $table->foreignId('advisor_signed_by_id')->nullable()->after('supervisor_signed_at')
                ->constrained('employees')->nullOnDelete();
            $table->timestamp('advisor_signed_at')->nullable()->after('advisor_signed_by_id');
        });

        foreach (['inspection_item_groups', 'inspection_items'] as $table) {
            if (! Schema::hasColumn($table, 'sequence_no')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedSmallInteger('sequence_no')->default(0)->after('is_active');
                });
            }
        }

        // Seed the order from the current alphabetical view, so nothing jumps
        // around before anyone has set a preference. `update … from` is Postgres
        // syntax and a fresh test database has nothing to seed anyway.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            update inspection_item_groups
            set sequence_no = ranked.rn
            from (select id, row_number() over (order by name) as rn from inspection_item_groups) ranked
            where inspection_item_groups.id = ranked.id and inspection_item_groups.sequence_no = 0
        SQL);

        DB::statement(<<<'SQL'
            update inspection_items
            set sequence_no = ranked.rn
            from (
                select id, row_number() over (partition by inspection_item_group_id order by name) as rn
                from inspection_items
            ) ranked
            where inspection_items.id = ranked.id and inspection_items.sequence_no = 0
        SQL);
    }

    public function down(): void
    {
        Schema::table('digital_inspections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('technician_signed_by_id');
            $table->dropConstrainedForeignId('supervisor_signed_by_id');
            $table->dropConstrainedForeignId('advisor_signed_by_id');
            $table->dropColumn([
                'explained_on_lift', 'media_shared', 'questions_answered',
                'customer_approval', 'customer_approval_at',
                'technician_signed_at', 'supervisor_signed_at', 'advisor_signed_at',
            ]);
        });

        foreach (['inspection_item_groups', 'inspection_items'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('sequence_no'));
        }
    }
};
