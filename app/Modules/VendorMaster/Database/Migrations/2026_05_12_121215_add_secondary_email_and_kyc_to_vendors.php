<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Optional secondary email per vendor.
 * - Aadhar number (proprietor-style vendors) + Aadhar/PAN scan files.
 * - Original filename kept alongside the storage path so downloads serve
 *   as the file the vendor originally uploaded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('secondary_email')->nullable()->after('email');
            $table->char('aadhar', 12)->nullable()->unique()->after('pan');
            $table->string('aadhar_file_path')->nullable()->after('aadhar');
            $table->string('aadhar_file_name')->nullable()->after('aadhar_file_path');
            $table->string('pan_file_path')->nullable()->after('pan');
            $table->string('pan_file_name')->nullable()->after('pan_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'secondary_email',
                'aadhar',
                'aadhar_file_path',
                'aadhar_file_name',
                'pan_file_path',
                'pan_file_name',
            ]);
        });
    }
};
