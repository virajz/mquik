<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_inspection_items', function (Blueprint $table) {
            $table->string('recommendation', 20)->nullable()->after('outcome');  // none | repair | replace | monitor | urgent
            $table->string('severity', 20)->nullable()->after('recommendation');  // low | medium | high | critical
            $table->text('observation')->nullable()->after('severity');           // standard comment
        });

        Schema::table('inspection_templates', function (Blueprint $table) {
            $table->string('frequency', 20)->nullable()->after('applies_to');  // every_service | 5000 | 10000
        });
    }

    public function down(): void
    {
        Schema::table('digital_inspection_items', function (Blueprint $table) {
            $table->dropColumn(['recommendation', 'severity', 'observation']);
        });

        Schema::table('inspection_templates', function (Blueprint $table) {
            $table->dropColumn('frequency');
        });
    }
};
