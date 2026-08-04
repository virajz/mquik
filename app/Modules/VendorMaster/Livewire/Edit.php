<?php

namespace App\Modules\VendorMaster\Livewire;

use App\Concerns\HasQuickCreate;
use App\Concerns\SearchesPickerOptions;
use App\Modules\BankMaster\Models\BankMaster;
use App\Modules\GstTypeMaster\Models\GstTypeMaster;
use App\Modules\InventoryGroupMaster\Models\InventoryGroupMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use App\Modules\ServiceSpecialistMaster\Models\ServiceSpecialistMaster;
use App\Modules\SpareBrandMaster\Models\SpareBrandMaster;
use App\Modules\VendorMaster\Models\VendorAttachment;
use App\Modules\VendorMaster\Models\VendorMaster;
use App\Modules\VendorMaster\Models\VendorTerm;
use App\Modules\VendorTypeMaster\Models\VendorTypeMaster;
use App\Support\ChildRows;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Vendor')]
class Edit extends Component
{
    use HasQuickCreate;
    use SearchesPickerOptions;
    use WithFileUploads;

    public ?int $editingId = null;

    public string $vendor_code = '';

    public string $name = '';

    /** @var list<int> */
    public array $vendor_type_ids = [];

    /** Combobox search string for the multi-select Type picker — also used as the "Create" name. */
    public string $vendorTypeSearch = '';

    /** @var list<int> */
    public array $spare_brand_ids = [];

    /** Search string for the multi-select Parts Brand picker — also used as the "Create" name. */
    public string $spareBrandSearch = '';

    public string $phone = '';

    public ?string $alternate_phone = null;

    public ?string $email = null;

    public ?string $secondary_email = null;

    public ?string $address = null;

    public ?int $gst_type_id = null;

    /** @var array<int, int> */
    public array $service_specialist_ids = [];

    /** @var array<int, int> inventory groups this vendor supplies */
    public array $inventory_group_ids = [];

    /** Search term for the inventory-group picker (~1,000 rows). */
    public string $inventoryGroupSearch = '';

    public ?int $region_id = null;

    /** Combobox search string for the Region picker — also used as the "Create" name. */
    public string $regionSearch = '';

    public ?string $aadhar = null;

    public ?string $pan = null;

    public ?string $gstin = null;

    public $aadhar_file = null;

    public $pan_file = null;

    public ?string $aadhar_file_path = null;

    public ?string $aadhar_file_name = null;

    public ?string $pan_file_path = null;

    public ?string $pan_file_name = null;

    public ?int $bank_id = null;

    /** Combobox search string for the Bank picker — also used as the "Create" name. */
    public string $bankSearch = '';

    public ?string $bank_branch = null;

    public ?string $ifsc = null;

    public ?string $account_no = null;

    public ?string $account_holder = null;

    public int $credit_days = 0;

    public float $credit_limit = 0;

    public bool $is_active = true;

    public ?string $notes = null;

    // --- Profile (module 97 extension) ---
    public ?string $legal_name = null;

    public ?string $registration_date = null;

    public ?string $reference = null;

    public ?string $contact_person1 = null;

    public ?string $contact_person2 = null;

    public ?string $branch_address = null;

    public ?string $pincode = null;

    public ?string $udyam_no = null;

    public ?string $classification = null;

    public ?string $constitution = null;

    public ?string $gst_registration_type = null;

    public ?string $msme_type = null;

    public ?string $msme_activity = null;

    public ?string $vendor_category = null;

    public string $vendor_status = 'active';

    public ?string $blacklist_reason = null;

    public ?int $rating = null;

    public ?string $delivery_method = null;

    public ?string $payment_terms = null;

    public ?float $on_time_delivery_percent = null;

    public ?float $parts_return_percent = null;

    public ?float $return_rejection_percent = null;

    public ?float $avg_response_hours = null;

    /** @var list<array{id: ?int, term_type: ?string, name: string, value: string}> */
    public array $terms = [];

    /** @var array<int, array{id:?int, attachment_type:?string, path:?string, original_name:?string, notes:?string}> */
    public array $attachments = [];

    public array $attachmentFiles = [];

    public function mount(?VendorMaster $vendor = null): void
    {
        if ($vendor && $vendor->exists) {
            $this->load($vendor);
        }
    }

    protected function load(VendorMaster $vendor): void
    {
        $vendor->load(['vendorTypes:id', 'spareBrands:id', 'terms', 'attachments']);

        $this->editingId = $vendor->id;
        foreach ([
            'vendor_code', 'name', 'phone', 'alternate_phone', 'email', 'secondary_email', 'address', 'aadhar', 'pan', 'gstin',
            'bank_branch', 'ifsc', 'account_no', 'account_holder', 'notes', 'aadhar_file_path', 'aadhar_file_name', 'pan_file_path', 'pan_file_name',
            'legal_name', 'reference', 'contact_person1', 'contact_person2', 'branch_address', 'pincode', 'udyam_no',
            'classification', 'constitution', 'gst_registration_type', 'msme_type', 'msme_activity', 'vendor_category',
            'blacklist_reason', 'delivery_method', 'payment_terms',
        ] as $k) {
            $this->{$k} = $vendor->{$k};
        }
        $this->vendor_status = $vendor->vendor_status ?? 'active';
        $this->registration_date = $vendor->registration_date?->format('Y-m-d');
        $this->rating = $vendor->rating;
        $this->on_time_delivery_percent = $vendor->on_time_delivery_percent === null ? null : (float) $vendor->on_time_delivery_percent;
        $this->parts_return_percent = $vendor->parts_return_percent === null ? null : (float) $vendor->parts_return_percent;
        $this->return_rejection_percent = $vendor->return_rejection_percent === null ? null : (float) $vendor->return_rejection_percent;
        $this->avg_response_hours = $vendor->avg_response_hours === null ? null : (float) $vendor->avg_response_hours;

        $this->attachments = $vendor->attachments->map(fn ($a) => [
            'id' => $a->id, 'attachment_type' => $a->attachment_type, 'path' => $a->path,
            'original_name' => $a->original_name, 'notes' => $a->notes,
        ])->all();
        $this->region_id = $vendor->region_id;
        $this->bank_id = $vendor->bank_id;
        $this->gst_type_id = $vendor->gst_type_id;
        $this->vendor_type_ids = $vendor->vendorTypes->pluck('id')->all();
        $this->spare_brand_ids = $vendor->spareBrands->pluck('id')->all();
        $this->service_specialist_ids = $vendor->serviceSpecialists->pluck('id')->all();
        $this->inventory_group_ids = $vendor->inventoryGroups->pluck('id')->all();
        $this->credit_days = (int) $vendor->credit_days;
        $this->credit_limit = (float) $vendor->credit_limit;
        $this->is_active = $vendor->is_active;

        $this->terms = $vendor->terms
            ->map(fn ($t) => [
                'id' => $t->id,
                'term_type' => $t->term_type,
                'name' => $t->name,
                'value' => $t->value,
            ])
            ->all();
    }

    protected function rules(): array
    {
        return [
            'vendor_code' => ['required', 'string', 'max:30', Rule::unique('vendors', 'vendor_code')->ignore($this->editingId)],
            'name' => ['required', 'string', 'max:255'],
            'vendor_type_ids' => ['required', 'array', 'min:1'],
            'vendor_type_ids.*' => ['integer', Rule::exists('vendor_types', 'id')->where('is_active', true)],
            'inventory_group_ids' => ['array'],
            'inventory_group_ids.*' => ['integer', Rule::exists('inventory_groups', 'id')->where('is_active', true)],
            'spare_brand_ids' => ['array'],
            'spare_brand_ids.*' => ['integer', Rule::exists('spare_brands', 'id')->where('is_active', true)],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'secondary_email' => ['nullable', 'email', 'max:255', 'different:email'],
            'address' => ['nullable', 'string', 'max:1000'],
            'gst_type_id' => ['nullable', 'integer', Rule::exists('gst_types', 'id')->where('is_active', true)],
            'service_specialist_ids' => ['array'],
            'service_specialist_ids.*' => ['integer', Rule::exists('service_specialists', 'id')->where('is_active', true)],
            'region_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('is_active', true)],
            'aadhar' => ['nullable', 'string', 'size:12', Rule::unique('vendors', 'aadhar')->ignore($this->editingId)],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/', Rule::unique('vendors', 'pan')->ignore($this->editingId)],
            'gstin' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', Rule::unique('vendors', 'gstin')->ignore($this->editingId)],
            'aadhar_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'pan_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'bank_id' => ['nullable', 'integer', Rule::exists('banks', 'id')->where('is_active', true)],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'ifsc' => ['nullable', 'string', 'size:11'],
            'account_no' => ['nullable', 'string', 'max:30'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'credit_days' => ['integer', 'min:0', 'max:365'],
            'credit_limit' => ['numeric', 'min:0', 'max:99999999.99'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'contact_person1' => ['nullable', 'string', 'max:255'],
            'contact_person2' => ['nullable', 'string', 'max:255'],
            'branch_address' => ['nullable', 'string', 'max:1000'],
            'pincode' => ['nullable', 'string', 'size:6'],
            'udyam_no' => ['nullable', 'string', 'max:25'],
            'classification' => ['nullable', Rule::in(array_keys(VendorMaster::classifications()))],
            'constitution' => ['nullable', Rule::in(array_keys(VendorMaster::constitutions()))],
            'gst_registration_type' => ['nullable', Rule::in(array_keys(VendorMaster::gstRegistrationTypes()))],
            'msme_type' => ['nullable', Rule::in(array_keys(VendorMaster::msmeTypes()))],
            'msme_activity' => ['nullable', Rule::in(array_keys(VendorMaster::msmeActivities()))],
            'vendor_category' => ['nullable', Rule::in(array_keys(VendorMaster::vendorCategories()))],
            'vendor_status' => ['required', Rule::in(array_keys(VendorMaster::vendorStatuses()))],
            'blacklist_reason' => ['nullable', Rule::in(array_keys(VendorMaster::blacklistReasons())), Rule::requiredIf(fn () => $this->vendor_status === 'blacklisted')],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'delivery_method' => ['nullable', Rule::in(array_keys(VendorMaster::deliveryMethods()))],
            'payment_terms' => ['nullable', Rule::in(array_keys(VendorMaster::paymentTermsOptions()))],
            'on_time_delivery_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'parts_return_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'return_rejection_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'avg_response_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],

            'terms' => ['array'],
            'terms.*.term_type' => ['nullable', Rule::in(array_keys(VendorTerm::termTypes()))],
            'terms.*.name' => ['required_with:terms.*.value', 'nullable', 'string', 'max:100'],
            'terms.*.value' => ['required_with:terms.*.name', 'nullable', 'string', 'max:1000'],

            'attachments' => ['array'],
            'attachments.*.attachment_type' => ['nullable', Rule::in(array_keys(VendorAttachment::attachmentTypes()))],
            'attachments.*.notes' => ['nullable', 'string', 'max:255'],
            'attachmentFiles.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ];
    }

    public function createVendorType(): void
    {
        $created = $this->quickCreate(
            modelClass: VendorTypeMaster::class,
            targetProperty: 'vendor_type_ids',
            searchProperty: 'vendorTypeSearch',
            permission: 'vendor_type_master.create',
            label: 'Vendor type',
            appendToList: true,
        );
        if ($created) {
            Flux::modal('vendor-type-quick-add')->close();
        }
    }

    public function createSpareBrand(): void
    {
        $created = $this->quickCreate(
            modelClass: SpareBrandMaster::class,
            targetProperty: 'spare_brand_ids',
            searchProperty: 'spareBrandSearch',
            permission: 'spare_brand_master.create',
            label: 'Parts brand',
            appendToList: true,
        );
        if ($created) {
            Flux::modal('spare-brand-quick-add')->close();
        }
    }

    public function createBank(): void
    {
        $this->quickCreate(
            modelClass: BankMaster::class,
            targetProperty: 'bank_id',
            searchProperty: 'bankSearch',
            permission: 'bank_master.create',
            label: 'Bank',
        );
    }

    /**
     * Region needs the caller-chosen kind, so it can't go through the trait.
     * Mirrors the customer-address flow but for the vendor's single address.
     */
    public function createRegion(string $kind): void
    {
        $this->authorize('region_master.create');

        if (! in_array($kind, RegionMaster::kinds(), true)) {
            return;
        }

        $name = strtoupper(trim($this->regionSearch));
        if ($name === '') {
            return;
        }

        $region = RegionMaster::firstOrCreate(
            ['kind' => $kind, 'parent_id' => null, 'name' => $name],
            ['is_active' => true],
        );

        $this->region_id = $region->id;
        $this->regionSearch = '';

        Flux::toast(
            text: ucfirst($kind).' "'.$region->name.'" added.',
            variant: 'success',
        );
    }

    /**
     * Inventory groups, searched server-side — there are ~1,000 sub-groups, far
     * too many to render into a select.
     */
    #[Computed]
    public function inventoryGroupOptions()
    {
        return $this->pickerOptions(
            query: InventoryGroupMaster::query()
                ->with('parent:id,name')
                ->where('is_active', true)
                ->orderBy('name'),
            searchColumns: ['name', 'code', 'parent.name'],
            term: $this->inventoryGroupSearch,
            selected: $this->inventory_group_ids,
            columns: ['id', 'name', 'code', 'parent_id'],
            limit: 30,
        );
    }

    #[Computed]
    public function gstTypes()
    {
        return GstTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function serviceSpecialistOptions()
    {
        return ServiceSpecialistMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function regions()
    {
        $kindOrder = ['pincode' => 0, 'area' => 1, 'city' => 2, 'state' => 3];

        return RegionMaster::query()
            ->with('parent.parent.parent')
            ->where('is_active', true)
            ->get()
            ->sortBy([
                fn ($a, $b) => ($kindOrder[$a->kind] ?? 99) <=> ($kindOrder[$b->kind] ?? 99),
                fn ($a, $b) => strcmp($a->name, $b->name),
            ])
            ->values()
            ->map(function ($r) {
                $chain = [];
                $node = $r->parent;
                $depth = 0;
                while ($node && $depth < 4) {
                    $chain[] = $node->name;
                    $node = $node->parent;
                    $depth++;
                }

                return [
                    'id' => $r->id,
                    'kind' => $r->kind,
                    'name' => $r->name,
                    'chain' => implode(', ', $chain),
                ];
            });
    }

    #[Computed]
    public function banks()
    {
        return BankMaster::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Searched server-side — the imported catalogue carries ~770 parts brands,
     * too many to render into a select and re-ship on every round trip.
     */
    #[Computed]
    public function spareBrands()
    {
        return $this->pickerOptions(
            query: SpareBrandMaster::query()->where('is_active', true)->orderBy('name'),
            searchColumns: ['name', 'code'],
            term: $this->spareBrandSearch,
            selected: $this->spare_brand_ids,
            columns: ['id', 'name'],
            limit: 30,
        );
    }

    public function addTerm(): void
    {
        $this->terms[] = ['id' => null, 'term_type' => null, 'name' => '', 'value' => ''];
    }

    public function addAttachment(): void
    {
        $this->attachments[] = ['id' => null, 'attachment_type' => null, 'path' => null, 'original_name' => null, 'notes' => null];
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index], $this->attachmentFiles[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function removeTerm(int $index): void
    {
        if (! isset($this->terms[$index])) {
            return;
        }
        unset($this->terms[$index]);
        $this->terms = array_values($this->terms);
    }

    public function removeAadharFile(): void
    {
        $this->aadhar_file = null;
        $this->aadhar_file_path = null;
        $this->aadhar_file_name = null;
    }

    public function removePanFile(): void
    {
        $this->pan_file = null;
        $this->pan_file_path = null;
        $this->pan_file_name = null;
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'vendor_master.update' : 'vendor_master.create');

        // Normalise formatted inputs before validation: uppercase the alphanumeric
        // codes, and strip the mask spaces from Aadhaar / phone (their masks bind the
        // separators into the value, which would otherwise fail the length rules).
        $this->pan = filled($this->pan) ? strtoupper(trim($this->pan)) : null;
        $this->gstin = filled($this->gstin) ? strtoupper(trim($this->gstin)) : null;
        $this->aadhar = filled($this->aadhar) ? preg_replace('/\D/', '', $this->aadhar) : null;
        $this->phone = preg_replace('/\D/', '', (string) $this->phone);
        $this->alternate_phone = filled($this->alternate_phone) ? preg_replace('/\D/', '', $this->alternate_phone) : null;

        // Drop empty term rows so blank-row noise doesn't fail validation.
        $this->terms = array_values(array_filter(
            $this->terms,
            fn ($t) => filled($t['name'] ?? null) || filled($t['value'] ?? null),
        ));

        $this->validate();

        $terms = $this->terms;
        $typeIds = array_map('intval', $this->vendor_type_ids);
        $brandIds = array_map('intval', $this->spare_brand_ids);
        $specialistIds = array_map('intval', $this->service_specialist_ids);
        $groupIds = array_map('intval', $this->inventory_group_ids);
        $aadharFile = $this->aadhar_file;
        $panFile = $this->pan_file;
        $aadharCleared = $this->aadhar_file_path === null;
        $panCleared = $this->pan_file_path === null;

        $attachments = $this->attachments;

        $data = collect($this->validate())->except([
            'vendor_type_ids', 'spare_brand_ids', 'service_specialist_ids', 'inventory_group_ids', 'terms', 'aadhar_file', 'pan_file',
            'attachments', 'attachmentFiles',
        ])->all();

        $skip = [
            'email', 'secondary_email', 'phone', 'alternate_phone', 'account_no', 'credit_days', 'credit_limit', 'is_active', 'aadhar', 'region_id', 'bank_id', 'gst_type_id',
            'registration_date', 'rating', 'classification', 'constitution', 'gst_registration_type', 'msme_type', 'msme_activity', 'vendor_category',
            'vendor_status', 'blacklist_reason', 'delivery_method', 'payment_terms',
            'on_time_delivery_percent', 'parts_return_percent', 'return_rejection_percent', 'avg_response_hours',
        ];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        $isCreate = $this->editingId === null;

        $vendor = DB::transaction(function () use ($data, $typeIds, $brandIds, $specialistIds, $groupIds, $terms, $attachments, $aadharFile, $panFile, $aadharCleared, $panCleared) {
            if ($this->editingId) {
                $v = VendorMaster::findOrFail($this->editingId);
                $v->update($data);
            } else {
                $v = VendorMaster::create($data);
                $this->editingId = $v->id;
            }

            $v->vendorTypes()->sync($typeIds);
            $v->spareBrands()->sync($brandIds);
            $v->serviceSpecialists()->sync($specialistIds);
            $v->inventoryGroups()->sync($groupIds);
            $this->syncTerms($v, $terms);
            $this->syncKycFile($v, 'aadhar', $aadharFile, $aadharCleared);
            $this->syncKycFile($v, 'pan', $panFile, $panCleared);
            $this->syncAttachments($v, $attachments);

            return $v;
        });

        $this->attachmentFiles = [];

        Flux::toast(
            text: 'Vendor #'.$vendor->id.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('vendor-master.index');
    }

    /**
     * @param  array<int, array{id?: int|null, name?: string, value?: string}>  $rows
     */
    protected function syncTerms(VendorMaster $vendor, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $i => $row) {
            $payload = [
                'term_type' => $row['term_type'] ?? null,
                'name' => trim((string) ($row['name'] ?? '')),
                'value' => trim((string) ($row['value'] ?? '')),
                'sort_order' => $i,
            ];

            if (! empty($row['id'])) {
                $existing = $vendor->terms()->whereKey($row['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $vendor->terms()->create($payload);
            $keptIds[] = $created->id;
        }

        $vendor->terms()->whereNotIn('id', $keptIds)->delete();
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    protected function syncAttachments(VendorMaster $vendor, array $rows): void
    {
        $keptIds = [];

        foreach (array_values($rows) as $i => $row) {
            $path = $this->attachments[$i]['path'] ?? null;
            $originalName = $this->attachments[$i]['original_name'] ?? null;
            $size = null;
            $kind = 'image';

            $upload = $this->attachmentFiles[$i] ?? null;
            if ($upload instanceof TemporaryUploadedFile) {
                $path = $upload->store('vendors/'.$vendor->id.'/attachments', 'public');
                $originalName = $upload->getClientOriginalName();
                $size = $upload->getSize();
                $kind = strtolower((string) $upload->getClientOriginalExtension()) === 'pdf' ? 'pdf' : 'image';
            }

            if ($path === null) {
                continue;
            }

            $keptIds[] = ChildRows::upsert($vendor->attachments(), $row['id'] ?? null,
                [
                    'attachment_type' => $row['attachment_type'] ?: null, 'kind' => $kind, 'path' => $path,
                    'original_name' => $originalName, 'size_bytes' => $size, 'notes' => $row['notes'] ?: null, 'sequence_no' => $i + 1,
                ],
            )->id;
        }

        $vendor->attachments()->whereKeyNot($keptIds)->delete();
    }

    protected function syncKycFile(VendorMaster $vendor, string $type, $newFile, bool $clearedByUser): void
    {
        $pathCol = $type.'_file_path';
        $nameCol = $type.'_file_name';
        $existing = $vendor->{$pathCol};

        if ($newFile instanceof TemporaryUploadedFile) {
            if ($existing) {
                Storage::delete($existing);
            }
            $path = $newFile->store("vendors/{$vendor->id}/{$type}");
            $vendor->forceFill([
                $pathCol => $path,
                $nameCol => $newFile->getClientOriginalName(),
            ])->save();

            return;
        }

        if ($clearedByUser && $existing) {
            Storage::delete($existing);
            $vendor->forceFill([$pathCol => null, $nameCol => null])->save();
        }
    }

    public function render()
    {
        return view('vendor-master::edit', [
            'vendorTypes' => VendorTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
