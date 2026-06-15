<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photo types become capture "slots" grouped into tabs (Exterior, Interior…).
        Schema::table('photo_types', function (Blueprint $table) {
            $table->string('group', 60)->default('GENERAL')->after('code');
            $table->unsignedInteger('sort_order')->default(0)->after('group');

            $table->index(['group', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('photo_types', function (Blueprint $table) {
            $table->dropIndex(['group', 'sort_order']);
            $table->dropColumn(['group', 'sort_order']);
        });
    }
};
