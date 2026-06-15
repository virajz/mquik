<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->foreignId('service_package_type_id')->nullable()->after('code')
                ->constrained('service_package_types')->nullOnDelete();
        });

        // Backfill existing packages from the is_amc flag: AMC packages → AMC,
        // everything else → Periodic Service. Find-or-create only what's needed,
        // so a fresh DB (tests) keeps the master table empty.
        if (DB::table('service_packages')->exists()) {
            $resolve = function (string $name): int {
                $id = DB::table('service_package_types')->where('name', $name)->value('id');

                return $id ?: DB::table('service_package_types')->insertGetId([
                    'name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            };

            if (DB::table('service_packages')->where('is_amc', true)->exists()) {
                DB::table('service_packages')->where('is_amc', true)->update(['service_package_type_id' => $resolve('AMC')]);
            }
            if (DB::table('service_packages')->where('is_amc', false)->exists()) {
                DB::table('service_packages')->where('is_amc', false)->update(['service_package_type_id' => $resolve('PERIODIC SERVICE')]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('service_packages', function (Blueprint $table) {
            $table->dropForeign(['service_package_type_id']);
            $table->dropColumn('service_package_type_id');
        });
    }
};
