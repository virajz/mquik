<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store a customer's uploaded GST certificate (path + original filename),
 * mirroring the existing Aadhaar / PAN KYC file columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('gst_certificate_file_path')->nullable()->after('gstin');
            $table->string('gst_certificate_file_name')->nullable()->after('gst_certificate_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['gst_certificate_file_path', 'gst_certificate_file_name']);
        });
    }
};
