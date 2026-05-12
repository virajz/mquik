<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - Optional secondary email per customer.
 * - Aadhar / PAN scanned file uploads. We keep the original filename in DB
 *   (alongside the storage path) so downloads serve as the file the client
 *   originally uploaded, not the hashed-filename we use on disk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('secondary_email')->nullable()->after('email');
            $table->string('aadhar_file_path')->nullable()->after('aadhar');
            $table->string('aadhar_file_name')->nullable()->after('aadhar_file_path');
            $table->string('pan_file_path')->nullable()->after('pan');
            $table->string('pan_file_name')->nullable()->after('pan_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'secondary_email',
                'aadhar_file_path',
                'aadhar_file_name',
                'pan_file_path',
                'pan_file_name',
            ]);
        });
    }
};
