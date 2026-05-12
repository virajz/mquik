<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original unique on (hsn_sac, gst_percent) was too narrow — real Indian GST allows
     * the same HSN at the same GST rate with different cess values (e.g. HSN 8703 at GST 28%
     * has separate cess slabs for luxury cars (17%) and luxury SUVs (22%)). The `code` column
     * already enforces row-level uniqueness; the composite was redundant + wrong.
     */
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropUnique(['hsn_sac', 'gst_percent']);
            $table->unique(['hsn_sac', 'gst_percent', 'cess_percent']);
        });
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropUnique(['hsn_sac', 'gst_percent', 'cess_percent']);
            $table->unique(['hsn_sac', 'gst_percent']);
        });
    }
};
