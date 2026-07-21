<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * These two lists were first built for Appointments (row 4), but Pickup/Drop
 * (row 5) needs the exact same values — Customer Not Available, Wrong Address,
 * Vehicle Not Ready, Driver Unavailable / Customer Request, Traffic Issue.
 *
 * Rename them to neutral shared masters so both modules (and later ones) can
 * point at one list instead of duplicating it. Foreign keys follow the rename,
 * so no data or constraint fixing is needed.
 */
return new class extends Migration
{
    /** @var array<string, string> old table => new table */
    private const RENAMES = [
        'appointment_cancel_reasons' => 'cancel_reasons',
        'appointment_pending_reasons' => 'pending_reasons',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $old => $new) {
            if (Schema::hasTable($old) && ! Schema::hasTable($new)) {
                Schema::rename($old, $new);
            }
        }
    }

    public function down(): void
    {
        foreach (self::RENAMES as $old => $new) {
            if (Schema::hasTable($new) && ! Schema::hasTable($old)) {
                Schema::rename($new, $old);
            }
        }
    }
};
