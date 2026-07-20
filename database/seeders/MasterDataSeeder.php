<?php

namespace Database\Seeders;

use App\Modules\AccountGroupMaster\Models\AccountGroupMaster;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\ChecklistTemplateMaster\Models\ChecklistTemplateMaster;
use App\Modules\ConsumableCategoryMaster\Models\ConsumableCategoryMaster;
use App\Modules\CourierCompanyMaster\Models\CourierCompanyMaster;
use App\Modules\EmployeeMaster\Models\EmployeeMaster;
use App\Modules\GstTypeMaster\Models\GstTypeMaster;
use App\Modules\InspectionItemGroupMaster\Models\InspectionItemGroupMaster;
use App\Modules\InspectionItemMaster\Models\InspectionItemMaster;
use App\Modules\InsuranceCompanyMaster\Models\InsuranceCompanyMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\JobCardCancelReasonMaster\Models\JobCardCancelReasonMaster;
use App\Modules\RequestedRepairMaster\Models\RequestedRepairMaster;
use App\Modules\ServiceTypeMaster\Models\ServiceTypeMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\VehicleBrandMaster\Models\VehicleBrandMaster;
use App\Modules\VehicleColorMaster\Models\VehicleColorMaster;
use App\Modules\VehicleInventoryItemMaster\Models\VehicleInventoryItemMaster;
use App\Modules\VehicleModelMaster\Models\VehicleModelMaster;
use App\Modules\VehicleSegmentMaster\Models\VehicleSegmentMaster;
use App\Modules\VehicleVariantMaster\Models\VehicleVariantMaster;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Imports the client's previous-ERP master data (converted to CSV under
 * database/seeders/data/master-data/). Idempotent — masters via firstOrCreate,
 * people/vehicles skipped when their legacy_id already exists.
 */
class MasterDataSeeder extends Seeder
{
    protected string $path;

    /** @var array<string, array{variant: int, model: int}> old VehModelID → new variant + model ids */
    protected array $variantByOldId = [];

    /** @var array<string, int> old CarColorID → new color id */
    protected array $colorByOldId = [];

    /** @var array<int, int> old SiteID → new customer id */
    protected array $customerBySiteId = [];

    /** @var array<string, int> uppercase GST-type name → id */
    protected array $gstTypeByName = [];

    public function run(): void
    {
        $this->path = database_path('seeders/data/master-data');

        $this->seedSimpleMasters();
        $this->seedVehicleHierarchy();
        $this->seedInventoryGroups();
        $this->seedAccountGroups();
        $this->seedInspection();
        $this->seedPeople();
        $this->seedCustomerVehicles();
    }

    /**
     * @return list<array<string, string>>
     */
    protected function csv(string $name): array
    {
        $file = $this->path.'/'.$name.'.csv';
        if (! is_file($file)) {
            $this->command?->warn("  · {$name}.csv missing — skipped");

            return [];
        }

        $rows = [];
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);
        if ($header !== false) {
            $header = array_map('trim', $header);
            $width = count($header);
            while (($r = fgetcsv($handle)) !== false) {
                $rows[] = array_combine($header, array_slice(array_pad($r, $width, ''), 0, $width));
            }
        }
        fclose($handle);

        return $rows;
    }

    /** Trim + uppercase (workshop convention); null when blank. */
    protected function up(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_strtoupper($value);
    }

    /** Uppercase + hard-cap to a column width (legacy data has over-long junk). */
    protected function cap(?string $value, int $length): ?string
    {
        $value = $this->up($value);

        return $value === null ? null : mb_substr($value, 0, $length);
    }

    // ── Tier 1 & 2 ────────────────────────────────────────────────────────────

    protected function seedSimpleMasters(): void
    {
        DB::transaction(function () {
            foreach ($this->csv('CarColor') as $r) {
                if ($name = $this->up($r['CarColorName'] ?? '')) {
                    VehicleColorMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                }
            }
            foreach ($this->csv('ServiceType') as $r) {
                if ($name = $this->up($r['ServiceName'] ?? '')) {
                    ServiceTypeMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                }
            }
            foreach ($this->csv('tbl_Consumable_Type') as $r) {
                if ($name = $this->up($r['ConsumableName'] ?? '')) {
                    ConsumableCategoryMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                }
            }
            foreach ($this->csv('InvInsideVeh') as $r) {
                if ($name = $this->up($r['InvInsideVehName'] ?? '')) {
                    VehicleInventoryItemMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                }
            }
            foreach ($this->csv('JCReason') as $r) {
                if ($name = $this->up($r['JCReason'] ?? '')) {
                    JobCardCancelReasonMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                }
            }
            foreach ($this->csv('Company') as $r) {
                if ($name = $this->up($r['CompanyName'] ?? '')) {
                    SpareBrandMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                }
            }
            foreach ($this->csv('Repairs') as $r) {
                if ($name = $this->up($r['RepairsName'] ?? '')) {
                    RequestedRepairMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                }
            }
        });
        $this->command?->info('  · simple masters imported (colors, service types, consumable types, in-vehicle items, JC reasons, spare brands, repairs)');
    }

    protected function seedVehicleHierarchy(): void
    {
        $segIdMap = [];
        foreach ($this->csv('Segment') as $r) {
            if ($name = $this->up($r['SegmentName'] ?? '')) {
                $segIdMap[$r['SegmentID']] = VehicleSegmentMaster::firstOrCreate(['name' => $name], ['is_active' => true])->id;
            }
        }

        DB::transaction(function () use ($segIdMap) {
            $brandMap = [];
            foreach ($this->csv('VehCompany') as $r) {
                if ($name = $this->up($r['VehCompanyName'] ?? '')) {
                    $brandMap[$r['VehCompanyID']] = VehicleBrandMaster::firstOrCreate(['name' => $name], ['is_active' => true])->id;
                }
            }

            $modelMap = [];
            foreach ($this->csv('Vehicle') as $r) {
                $brandId = $brandMap[$r['VehCompanyID']] ?? null;
                $name = $this->up($r['VehicleName'] ?? '');
                if (! $brandId || ! $name) {
                    continue;
                }
                $model = VehicleModelMaster::firstOrCreate(
                    ['brand_id' => $brandId, 'name' => $name],
                    ['vehicle_segment_id' => $segIdMap[$r['SegmentID']] ?? null, 'is_active' => true],
                );
                $modelMap[$r['VehicleID']] = $model->id;
            }

            foreach ($this->csv('VehModel') as $r) {
                $modelId = $modelMap[$r['VehicleID']] ?? null;
                $name = $this->up($r['VehModelName'] ?? '');
                if (! $modelId || ! $name) {
                    continue;
                }
                $variant = VehicleVariantMaster::firstOrCreate(
                    ['model_id' => $modelId, 'name' => $name],
                    ['engine_cc' => $this->up($r['Engine'] ?? ''), 'is_active' => true],
                );
                $this->variantByOldId[$r['VehModelID']] = ['variant' => $variant->id, 'model' => $modelId];
            }
        });
        $this->command?->info('  · vehicle hierarchy imported (brands → models → variants)');
    }

    protected function seedInventoryGroups(): void
    {
        DB::transaction(function () {
            $groupMap = [];
            foreach ($this->csv('InvGroup') as $r) {
                if ($name = $this->up($r['InvGroupName'] ?? '')) {
                    $groupMap[$r['InvGroupID']] = InventoryGroupMaster::firstOrCreate(['name' => $name], ['parent_id' => null, 'is_active' => true])->id;
                }
            }
            foreach ($this->csv('InvSubGroup') as $r) {
                if ($name = $this->up($r['InvSubGroupName'] ?? '')) {
                    InventoryGroupMaster::firstOrCreate(['name' => $name], ['parent_id' => $groupMap[$r['InvGroupID']] ?? null, 'is_active' => true]);
                }
            }
        });
        $this->command?->info('  · inventory groups + sub-groups imported');
    }

    protected function seedAccountGroups(): void
    {
        DB::transaction(function () {
            foreach (['AccMainGroup' => 'MainGroupName', 'AccSubGroup' => 'SubGroupName'] as $file => $col) {
                foreach ($this->csv($file) as $r) {
                    if ($name = $this->up($r[$col] ?? '')) {
                        AccountGroupMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                    }
                }
            }
        });
        $this->command?->info('  · account groups imported');
    }

    protected function seedInspection(): void
    {
        DB::transaction(function () {
            $groupMap = [];
            foreach ($this->csv('CheckListGroup') as $r) {
                if ($name = $this->up($r['CheckListGroupName'] ?? '')) {
                    $groupMap[$r['CheckListGroupID']] = InspectionItemGroupMaster::firstOrCreate(['name' => $name], ['is_active' => true])->id;
                }
            }

            $itemNameMap = [];
            foreach ($this->csv('CheckListItem') as $r) {
                $name = $this->up($r['CheckListItemName'] ?? '');
                if (! $name) {
                    continue;
                }
                InspectionItemMaster::firstOrCreate(
                    ['name' => $name, 'inspection_item_group_id' => $groupMap[$r['CheckListGroupID']] ?? null],
                    ['check_type' => 'visual', 'is_active' => true],
                );
                $itemNameMap[$r['CheckListItemID']] = $name;
            }

            $collections = [];
            foreach ($this->csv('CheckListCollection') as $r) {
                $collectionName = $this->up($r['CollectionName'] ?? '');
                $label = $itemNameMap[$r['CheckListItemID']] ?? null;
                if (! $collectionName || ! $label) {
                    continue;
                }
                $collections[$collectionName][] = ['label' => $label, 'is_required' => ($r['IsActive'] ?? '1') === '1'];
            }
            foreach ($collections as $name => $items) {
                ChecklistTemplateMaster::firstOrCreate(
                    ['name' => $name],
                    ['applies_to' => 'inspection', 'checklist_group_id' => null, 'items' => $items, 'is_active' => true],
                );
            }
        });
        $this->command?->info('  · inspection groups, items + checklist templates imported');
    }

    // ── Tier 3: people (Site) + vehicles (SiteVeh) ────────────────────────────

    /** Old UserTypeID → target entity. */
    protected function route(string $userTypeId): string
    {
        return match ($userTypeId) {
            '6' => 'customer',
            '7', '11' => 'vendor',        // Vendor + Other Expense Party
            '13' => 'insurance',
            '9' => 'bank',
            '8' => 'courier',
            '1', '2', '3', '4', '5', '10', '12' => 'employee', // staff roles
            default => 'skip',
        };
    }

    /**
     * First ≥10-digit contact number + the next as alternate.
     *
     * @param  array<string, string>  $r
     * @return array{0: string, 1: ?string}
     */
    protected function phones(array $r): array
    {
        $nums = [];
        foreach (['ContactNo1', 'ContactNo2', 'ContactNo3', 'ContactNo4'] as $k) {
            $d = preg_replace('/\D+/', '', $r[$k] ?? '');
            if (strlen((string) $d) >= 10) {
                $nums[] = substr((string) $d, -15);
            }
        }

        return [$nums[0] ?? '0000000000', $nums[1] ?? null];
    }

    protected function email(?string $value): ?string
    {
        $value = trim((string) $value);

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? mb_strtolower($value) : null;
    }

    /**
     * @param  array<string, string>  $r
     */
    protected function address(array $r): ?string
    {
        $parts = array_filter([trim($r['Address1'] ?? ''), trim($r['Address2'] ?? '')]);

        return $parts ? mb_strtoupper(implode(', ', $parts)) : null;
    }

    /**
     * @return array{0: ?string, 1: ?string} [first_name, last_name]
     */
    protected function splitName(?string $full): array
    {
        $full = trim(preg_replace('/\s+/', ' ', (string) $full));
        if ($full === '') {
            return [null, null];
        }
        $parts = explode(' ', mb_strtoupper($full), 2);

        return [$parts[0], $parts[1] ?? null];
    }

    /** Parse a legacy date; treat 1900/1901 sentinels + junk as null. */
    protected function dateOrNull(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        try {
            $d = Carbon::parse($value);

            return $d->year > 1901 ? $d->format('Y-m-d') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function seedPeople(): void
    {
        $this->gstTypeByName = GstTypeMaster::pluck('id', 'name')->all();

        // Idempotency: skip customers already imported; keep their map for vehicles.
        $this->customerBySiteId = DB::table('customers')->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();

        $now = now();
        $newCustomers = [];
        $counts = ['customer' => 0, 'vendor' => 0, 'employee' => 0, 'insurance' => 0, 'bank' => 0, 'courier' => 0];

        foreach ($this->csv('Site') as $r) {
            $legacy = (int) $r['SiteID'];
            $name = $this->up($r['Site'] ?? '');
            if (! $name) {
                continue;
            }
            [$phone, $alt] = $this->phones($r);

            switch ($this->route($r['UserTypeID'] ?? '')) {
                case 'customer':
                    if (isset($this->customerBySiteId[$legacy])) {
                        break;
                    }
                    [$first, $last] = $this->splitName($r['Site'] ?? '');
                    $newCustomers[] = [
                        'legacy_id' => $legacy, 'first_name' => $first, 'last_name' => $last,
                        'phone' => $phone, 'alternate_phone' => $alt, 'email' => $this->email($r['Email1'] ?? ''),
                        'gst_type_id' => $this->gstTypeByName[$this->up($r['GSTType'] ?? '')] ?? null,
                        'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                    ];
                    $counts['customer']++;
                    break;

                case 'vendor':
                    VendorMaster::firstOrCreate(['legacy_id' => $legacy], [
                        'vendor_code' => 'VEN-'.$legacy, 'name' => $name, 'phone' => $phone, 'alternate_phone' => $alt,
                        'email' => $this->email($r['Email1'] ?? ''), 'address' => $this->address($r),
                        'gst_type_id' => $this->gstTypeByName[$this->up($r['GSTType'] ?? '')] ?? null,
                        'is_active' => true,
                    ]);
                    $counts['vendor']++;
                    break;

                case 'employee':
                    EmployeeMaster::firstOrCreate(['legacy_id' => $legacy], [
                        'employee_code' => 'EMP-'.$legacy, 'name' => $name, 'phone' => $phone, 'alternate_phone' => $alt,
                        'email' => $this->email($r['Email1'] ?? ''), 'address' => $this->address($r),
                        'joining_date' => $this->dateOrNull($r['JoinDate'] ?? '') ?? '2018-01-01',
                        'exit_date' => $this->dateOrNull($r['LeaveDate'] ?? ''), 'is_active' => true,
                    ]);
                    $counts['employee']++;
                    break;

                case 'insurance':
                    InsuranceCompanyMaster::firstOrCreate(['name' => $name], [
                        'phone' => $phone === '0000000000' ? null : $phone, 'address' => $this->address($r), 'is_active' => true,
                    ]);
                    $counts['insurance']++;
                    break;

                case 'bank':
                    BankMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                    $counts['bank']++;
                    break;

                case 'courier':
                    CourierCompanyMaster::firstOrCreate(['name' => $name], ['is_active' => true]);
                    $counts['courier']++;
                    break;
            }
        }

        foreach (array_chunk($newCustomers, 1000) as $chunk) {
            DB::table('customers')->insert($chunk);
        }

        $this->customerBySiteId = DB::table('customers')->whereNotNull('legacy_id')->pluck('id', 'legacy_id')->all();

        $this->command?->info(sprintf(
            '  · people imported (customers +%d, vendors +%d, employees +%d, insurance +%d, banks +%d, couriers +%d)',
            $counts['customer'], $counts['vendor'], $counts['employee'], $counts['insurance'], $counts['bank'], $counts['courier'],
        ));
    }

    protected function seedCustomerVehicles(): void
    {
        // old CarColorID → new color id
        foreach ($this->csv('CarColor') as $r) {
            if ($name = $this->up($r['CarColorName'] ?? '')) {
                $this->colorByOldId[$r['CarColorID']] = VehicleColorMaster::firstOrCreate(['name' => $name], ['is_active' => true])->id;
            }
        }

        $existing = DB::table('customer_vehicles')->whereNotNull('legacy_id')->pluck('legacy_id')->flip()->all();
        $seenReg = array_flip(DB::table('customer_vehicles')->pluck('registration_no')->map(fn ($x) => mb_strtoupper((string) $x))->all());
        $seenVin = array_flip(DB::table('customer_vehicles')->whereNotNull('vin')->pluck('vin')->map(fn ($x) => mb_strtoupper((string) $x))->all());

        $now = now();
        $new = [];
        $skipped = ['no_customer' => 0, 'no_model' => 0, 'no_reg' => 0, 'dup_reg' => 0];

        foreach ($this->csv('SiteVeh') as $r) {
            $legacy = (int) $r['SiteVehID'];
            if (isset($existing[$legacy])) {
                continue;
            }
            $customerId = $this->customerBySiteId[(int) $r['SiteID']] ?? null;
            if (! $customerId) {
                $skipped['no_customer']++;

                continue;
            }
            $variant = $this->variantByOldId[$r['VehModelID']] ?? null;
            $modelId = $variant['model'] ?? null;
            if (! $modelId) {
                $skipped['no_model']++;

                continue;
            }
            $reg = $this->cap($r['SiteVehNo'] ?? '', 20);
            if (! $reg) {
                $skipped['no_reg']++;

                continue;
            }
            if (isset($seenReg[$reg])) {
                $skipped['dup_reg']++;

                continue;
            }
            $seenReg[$reg] = true;

            // vin is unique — null out duplicates (the vehicle is still kept, keyed by reg).
            $vin = $this->cap($r['ChassisNo'] ?? '', 17);
            if ($vin !== null && isset($seenVin[$vin])) {
                $vin = null;
            } elseif ($vin !== null) {
                $seenVin[$vin] = true;
            }

            $new[] = [
                'legacy_id' => $legacy, 'customer_id' => $customerId, 'model_id' => $modelId,
                'variant_id' => $variant['variant'] ?? null, 'color_id' => $this->colorByOldId[$r['CarColorID']] ?? null,
                'registration_no' => $reg, 'engine_no' => $this->cap($r['EngineNo'] ?? '', 30), 'vin' => $vin,
                'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ];
        }

        foreach (array_chunk($new, 1000) as $chunk) {
            DB::table('customer_vehicles')->insert($chunk);
        }

        $this->command?->info(sprintf(
            '  · vehicles imported (+%d; skipped: %d no-customer, %d no-model, %d no-reg, %d dup-reg)',
            count($new), $skipped['no_customer'], $skipped['no_model'], $skipped['no_reg'], $skipped['dup_reg'],
        ));
    }
}
