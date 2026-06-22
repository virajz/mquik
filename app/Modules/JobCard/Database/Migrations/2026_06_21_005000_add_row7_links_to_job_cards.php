<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->foreignId('job_description_id')->nullable()->after('service_package_id')->constrained('job_descriptions')->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->after('job_description_id')->constrained('insurance_companies')->nullOnDelete();
            $table->string('policy_no', 60)->nullable()->after('insurance_company_id');
            $table->foreignId('vendor_id')->nullable()->after('policy_no')->constrained('vendors')->nullOnDelete();
            $table->foreignId('customer_approval_type_id')->nullable()->after('vendor_id')->constrained('customer_approval_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('job_description_id');
            $table->dropConstrainedForeignId('insurance_company_id');
            $table->dropColumn('policy_no');
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropConstrainedForeignId('customer_approval_type_id');
        });
    }
};
