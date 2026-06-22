<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\AccountGroupMaster\Database\Seeders\AccountGroupMasterSeeder;
use App\Modules\BankMaster\Database\Seeders\BankMasterSeeder;
use App\Modules\BayMaster\Database\Seeders\BayMasterSeeder;
use App\Modules\BusinessTypeMaster\Database\Seeders\BusinessTypeMasterSeeder;
use App\Modules\ChecklistGroupMaster\Database\Seeders\ChecklistGroupMasterSeeder;
use App\Modules\ChecklistTemplateMaster\Database\Seeders\ChecklistTemplateMasterSeeder;
use App\Modules\ClaimTypeMaster\Database\Seeders\ClaimTypeMasterSeeder;
use App\Modules\CompanyMaster\Database\Seeders\CompanyMasterSeeder;
use App\Modules\ComplaintTypeMaster\Database\Seeders\ComplaintTypeMasterSeeder;
use App\Modules\ConsumableDepartmentMaster\Database\Seeders\ConsumableDepartmentMasterSeeder;
use App\Modules\CourierCompanyMaster\Database\Seeders\CourierCompanyMasterSeeder;
use App\Modules\CustomerApprovalTypeMaster\Database\Seeders\CustomerApprovalTypeMasterSeeder;
use App\Modules\CustomerMaster\Database\Seeders\CustomerMasterSeeder;
use App\Modules\CustomerVehicleMaster\Database\Seeders\CustomerVehicleMasterSeeder;
use App\Modules\DamageCauseMaster\Database\Seeders\DamageCauseMasterSeeder;
use App\Modules\EstimateRevisionReasonMaster\Database\Seeders\EstimateRevisionReasonMasterSeeder;
use App\Modules\EstimateTemplateMaster\Database\Seeders\EstimateTemplateMasterSeeder;
use App\Modules\DamageTypeMaster\Database\Seeders\DamageTypeMasterSeeder;
use App\Modules\DelayReasonMaster\Database\Seeders\DelayReasonMasterSeeder;
use App\Modules\DepartmentMaster\Database\Seeders\DepartmentMasterSeeder;
use App\Modules\DesignationMaster\Database\Seeders\DesignationMasterSeeder;
use App\Modules\DocumentCollection\Database\Seeders\DocumentCollectionSeeder;
use App\Modules\DocumentRejectionReasonMaster\Database\Seeders\DocumentRejectionReasonMasterSeeder;
use App\Modules\EmployeeMaster\Database\Seeders\EmployeeMasterSeeder;
use App\Modules\EnquirySourceMaster\Database\Seeders\EnquirySourceMasterSeeder;
use App\Modules\FollowUpModeMaster\Database\Seeders\FollowUpModeMasterSeeder;
use App\Modules\FuelTypeMaster\Database\Seeders\FuelTypeMasterSeeder;
use App\Modules\GstTypeMaster\Database\Seeders\GstTypeMasterSeeder;
use App\Modules\InspectionItemGroupMaster\Database\Seeders\InspectionItemGroupMasterSeeder;
use App\Modules\InspectionItemMaster\Database\Seeders\InspectionItemMasterSeeder;
use App\Modules\InspectionTemplateMaster\Database\Seeders\InspectionTemplateMasterSeeder;
use App\Modules\InsuranceCompanyMaster\Database\Seeders\InsuranceCompanyMasterSeeder;
use App\Modules\InsurancePolicyTypeMaster\Database\Seeders\InsurancePolicyTypeMasterSeeder;
use App\Modules\InventoryGroupMaster\Database\Seeders\InventoryGroupMasterSeeder;
use App\Modules\JobCardCancelReasonMaster\Database\Seeders\JobCardCancelReasonMasterSeeder;
use App\Modules\JobCardPendingReasonMaster\Database\Seeders\JobCardPendingReasonMasterSeeder;
use App\Modules\JobDescriptionMaster\Database\Seeders\JobDescriptionMasterSeeder;
use App\Modules\JobStageMaster\Database\Seeders\JobStageMasterSeeder;
use App\Modules\LocationMaster\Database\Seeders\LocationMasterSeeder;
use App\Modules\MissingDocumentReasonMaster\Database\Seeders\MissingDocumentReasonMasterSeeder;
use App\Modules\PartTypeMaster\Database\Seeders\PartTypeMasterSeeder;
use App\Modules\PaymentModeMaster\Database\Seeders\PaymentModeMasterSeeder;
use App\Modules\RackMaster\Database\Seeders\RackMasterSeeder;
use App\Modules\PhotoTypeMaster\Database\Seeders\PhotoTypeMasterSeeder;
use App\Modules\RegionMaster\Database\Seeders\RegionMasterSeeder;
use App\Modules\RegistrationTypeMaster\Database\Seeders\RegistrationTypeMasterSeeder;
use App\Modules\RequestedRepairMaster\Database\Seeders\RequestedRepairMasterSeeder;
use App\Modules\ReworkReasonMaster\Database\Seeders\ReworkReasonMasterSeeder;
use App\Modules\ServicePackageTypeMaster\Database\Seeders\ServicePackageTypeMasterSeeder;
use App\Modules\ServiceTypeMaster\Database\Seeders\ServiceTypeMasterSeeder;
use App\Modules\SpareBrandMaster\Database\Seeders\SpareBrandMasterSeeder;
use App\Modules\StandardObservationMaster\Database\Seeders\StandardObservationMasterSeeder;
use App\Modules\TaxMaster\Database\Seeders\TaxMasterSeeder;
use App\Modules\TechnicianFinding\Database\Seeders\TechnicianFindingSeeder;
use App\Modules\TransmissionTypeMaster\Database\Seeders\TransmissionTypeMasterSeeder;
use App\Modules\UnitOfMeasureMaster\Database\Seeders\UnitOfMeasureMasterSeeder;
use App\Modules\VehicleBrandMaster\Database\Seeders\VehicleBrandMasterSeeder;
use App\Modules\VehicleColorMaster\Database\Seeders\VehicleColorMasterSeeder;
use App\Modules\VehicleInspectionOrder\Database\Seeders\VehicleInspectionOrderSeeder;
use App\Modules\VehicleInventoryItemMaster\Database\Seeders\VehicleInventoryItemMasterSeeder;
use App\Modules\VehicleModelMaster\Database\Seeders\VehicleModelMasterSeeder;
use App\Modules\VehicleSegmentMaster\Database\Seeders\VehicleSegmentMasterSeeder;
use App\Modules\VehicleVariantMaster\Database\Seeders\VehicleVariantMasterSeeder;
use App\Modules\VendorMaster\Database\Seeders\VendorMasterSeeder;
use App\Modules\VendorTypeMaster\Database\Seeders\VendorTypeMasterSeeder;
use App\Modules\WorkOrderHoldReasonMaster\Database\Seeders\WorkOrderHoldReasonMasterSeeder;
use App\Modules\WorkshopDepartmentMaster\Database\Seeders\WorkshopDepartmentMasterSeeder;
use Database\Seeders\Auth\SuperAdminSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Make sure every module-declared permission exists in the DB.
        Artisan::call('auth:sync-permissions');

        // 2) Foundational masters — no FK dependencies on other masters.
        $this->call([
            RegionMasterSeeder::class,
            BankMasterSeeder::class,
            BusinessTypeMasterSeeder::class,
            GstTypeMasterSeeder::class,
            AccountGroupMasterSeeder::class,
            TaxMasterSeeder::class,
            EnquirySourceMasterSeeder::class,
            ComplaintTypeMasterSeeder::class,
            ClaimTypeMasterSeeder::class,
            InsurancePolicyTypeMasterSeeder::class,
            MissingDocumentReasonMasterSeeder::class,
            DocumentRejectionReasonMasterSeeder::class,
            FollowUpModeMasterSeeder::class,
            PhotoTypeMasterSeeder::class,
            DamageTypeMasterSeeder::class,
            CustomerApprovalTypeMasterSeeder::class,
            FuelTypeMasterSeeder::class,
            TransmissionTypeMasterSeeder::class,
            RegistrationTypeMasterSeeder::class,
            ServicePackageTypeMasterSeeder::class,
            RequestedRepairMasterSeeder::class,
            PaymentModeMasterSeeder::class,
            UnitOfMeasureMasterSeeder::class,
            JobCardCancelReasonMasterSeeder::class,
            JobCardPendingReasonMasterSeeder::class,
            JobStageMasterSeeder::class,
            BayMasterSeeder::class,
            WorkOrderHoldReasonMasterSeeder::class,
            ReworkReasonMasterSeeder::class,
            DelayReasonMasterSeeder::class,
            StandardObservationMasterSeeder::class,
            PartTypeMasterSeeder::class,
            RackMasterSeeder::class,
            DamageCauseMasterSeeder::class,
            EstimateRevisionReasonMasterSeeder::class,
            EstimateTemplateMasterSeeder::class,
            ConsumableDepartmentMasterSeeder::class,
            VehicleSegmentMasterSeeder::class,
            VehicleColorMasterSeeder::class,
            SpareBrandMasterSeeder::class,
            InsuranceCompanyMasterSeeder::class,
            VendorTypeMasterSeeder::class,
            CourierCompanyMasterSeeder::class,
            DepartmentMasterSeeder::class,
            DesignationMasterSeeder::class,
            WorkshopDepartmentMasterSeeder::class,
            VehicleInventoryItemMasterSeeder::class,
            ChecklistGroupMasterSeeder::class,
            InventoryGroupMasterSeeder::class,
            InspectionItemGroupMasterSeeder::class,
        ]);

        // 3) First-level FK deps.
        $this->call([
            VehicleBrandMasterSeeder::class,
            VendorMasterSeeder::class,
            EmployeeMasterSeeder::class,
            ServiceTypeMasterSeeder::class,
            CustomerMasterSeeder::class,
            LocationMasterSeeder::class,
            InspectionItemMasterSeeder::class,
            ChecklistTemplateMasterSeeder::class,
        ]);

        // 4) Second-level FK deps.
        $this->call([
            VehicleModelMasterSeeder::class,
            JobDescriptionMasterSeeder::class,
            InspectionTemplateMasterSeeder::class,
        ]);

        // 5) Third-level FK deps.
        $this->call([
            VehicleVariantMasterSeeder::class,
        ]);

        // 6) Fourth-level FK deps.
        $this->call([
            CustomerVehicleMasterSeeder::class,
        ]);

        // 6b) Transactions that depend on customers + vehicles.
        $this->call([
            DocumentCollectionSeeder::class,
            VehicleInspectionOrderSeeder::class,
            TechnicianFindingSeeder::class,
        ]);

        // 7) Singleton.
        $this->call([
            CompanyMasterSeeder::class,
        ]);

        // 8) Test user — idempotent; safe to re-seed.
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        );
        $testUser->forceFill(['email_verified_at' => now()])->save();

        // 9) Super Admin role + grant ALL permissions + assign to all existing users (including test@example.com).
        $this->call(SuperAdminSeeder::class);

        // 10) Defensive re-grant — make sure test@example.com is Super Admin even if it was demoted previously.
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        if (! $testUser->fresh()->hasRole($superAdminRole->name)) {
            $testUser->assignRole($superAdminRole);
        }
    }
}
