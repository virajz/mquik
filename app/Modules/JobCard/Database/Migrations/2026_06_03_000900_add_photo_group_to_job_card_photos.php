<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The tab a photo belongs to. For slot photos it mirrors the photo type's
        // group; for "additional / extra" shots it is set directly (photo_type_id null).
        Schema::table('job_card_photos', function (Blueprint $table) {
            $table->string('photo_group', 60)->nullable()->after('photo_type_id');

            $table->index(['job_card_id', 'photo_group']);
        });
    }

    public function down(): void
    {
        Schema::table('job_card_photos', function (Blueprint $table) {
            $table->dropIndex(['job_card_id', 'photo_group']);
            $table->dropColumn('photo_group');
        });
    }
};
