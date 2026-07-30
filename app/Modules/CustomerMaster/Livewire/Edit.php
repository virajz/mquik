<?php

namespace App\Modules\CustomerMaster\Livewire;

use App\Concerns\CanQuickAddCustomer;
use App\Concerns\HasQuickCreate;
use App\Modules\BusinessTypeMaster\Models\BusinessTypeMaster;
use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\GstTypeMaster\Models\GstTypeMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
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
#[Title('Customer')]
class Edit extends Component
{
    use CanQuickAddCustomer;
    use HasQuickCreate;
    use WithFileUploads;

    public ?int $editingId = null;

    public string $first_name = '';

    public ?string $middle_name = null;

    public ?string $last_name = null;

    public ?int $business_type_id = null;

    public ?int $gst_type_id = null;

    public ?string $gstin = null;

    /** Combobox search string for the Type picker — also used as the "Create" name. */
    public string $businessTypeSearch = '';

    public ?int $referred_by_customer_id = null;

    public string $phone = '';

    public ?string $alternate_phone = null;

    public ?string $email = null;

    public ?string $secondary_email = null;

    public ?string $aadhar = null;

    public ?string $pan = null;

    /** Pending Aadhar upload (TemporaryUploadedFile) — null if no new file staged. */
    public $aadhar_file = null;

    /** Pending PAN upload — null if no new file staged. */
    public $pan_file = null;

    /** Existing file metadata loaded on edit, mutated by remove actions. */
    public ?string $aadhar_file_path = null;

    public ?string $aadhar_file_name = null;

    public ?string $pan_file_path = null;

    public ?string $pan_file_name = null;

    public $gst_certificate_file = null;

    public ?string $gst_certificate_file_path = null;

    public ?string $gst_certificate_file_name = null;

    public ?string $date_of_birth = null;

    public ?string $notes = null;

    public bool $is_active = true;

    /** @var list<array{id: ?int, label: ?string, address_line: ?string, region_id: ?int, is_primary: bool}> */
    public array $addresses = [];

    public function mount(?CustomerMaster $customer = null): void
    {
        if ($customer && $customer->exists) {
            $this->load($customer);

            return;
        }

        $this->addresses = [$this->blankAddress(true)];
    }

    protected function load(CustomerMaster $customer): void
    {
        $customer->load('addresses');

        $this->editingId = $customer->id;
        $this->first_name = (string) $customer->first_name;
        $this->middle_name = $customer->middle_name;
        $this->last_name = $customer->last_name;
        $this->business_type_id = $customer->business_type_id;
        $this->gst_type_id = $customer->gst_type_id;
        $this->gstin = $customer->gstin;
        $this->referred_by_customer_id = $customer->referred_by_customer_id;
        $this->phone = $customer->phone;
        $this->alternate_phone = $customer->alternate_phone;
        $this->email = $customer->email;
        $this->secondary_email = $customer->secondary_email;
        $this->aadhar = $customer->aadhar;
        $this->pan = $customer->pan;
        $this->aadhar_file_path = $customer->aadhar_file_path;
        $this->aadhar_file_name = $customer->aadhar_file_name;
        $this->pan_file_path = $customer->pan_file_path;
        $this->pan_file_name = $customer->pan_file_name;
        $this->gst_certificate_file_path = $customer->gst_certificate_file_path;
        $this->gst_certificate_file_name = $customer->gst_certificate_file_name;
        $this->date_of_birth = $customer->date_of_birth?->format('Y-m-d');
        $this->notes = $customer->notes;
        $this->is_active = $customer->is_active;

        $this->addresses = $customer->addresses
            ->sortByDesc('is_primary')
            ->values()
            ->map(fn ($a) => [
                'id' => $a->id,
                'label' => $a->label,
                'address_line' => $a->address_line,
                'region_id' => $a->region_id,
                'regionSearch' => '',
                'is_primary' => (bool) $a->is_primary,
            ])
            ->all();

        if (empty($this->addresses)) {
            $this->addresses = [$this->blankAddress(true)];
        }
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'business_type_id' => ['required', 'integer', Rule::exists('business_types', 'id')->where('is_active', true)],
            'gst_type_id' => ['nullable', 'integer', Rule::exists('gst_types', 'id')->where('is_active', true)],
            'gstin' => [
                'nullable', 'string', 'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                Rule::unique('customers', 'gstin')->ignore($this->editingId),
            ],
            'referred_by_customer_id' => [
                'nullable', 'integer',
                $this->editingId !== null
                    ? Rule::exists('customers', 'id')->whereNot('id', $this->editingId)
                    : Rule::exists('customers', 'id'),
            ],
            'phone' => ['required', 'string', 'min:10', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'secondary_email' => ['nullable', 'email', 'max:255', 'different:email'],
            'aadhar' => [
                'nullable', 'string', 'size:12',
                Rule::unique('customers', 'aadhar')->ignore($this->editingId),
            ],
            'pan' => [
                'nullable', 'string', 'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/',
                Rule::unique('customers', 'pan')->ignore($this->editingId),
            ],
            'aadhar_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'pan_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'gst_certificate_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],

            'addresses' => ['array'],
            'addresses.*.label' => ['nullable', 'string', 'max:50'],
            'addresses.*.address_line' => ['nullable', 'string', 'max:1000'],
            'addresses.*.region_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('is_active', true)],
            'addresses.*.is_primary' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'addresses.*.region_id.exists' => 'Selected region is invalid or inactive.',
            'referred_by_customer_id.exists' => 'Selected referrer is invalid (cannot self-refer).',
        ];
    }

    /** Customer quick-add (CanQuickAddCustomer trait) writes the new id back to the referrer picker. */
    protected function quickCustomerTargetProperty(): string
    {
        return 'referred_by_customer_id';
    }

    public function createBusinessType(): void
    {
        $this->quickCreate(
            modelClass: BusinessTypeMaster::class,
            targetProperty: 'business_type_id',
            searchProperty: 'businessTypeSearch',
            permission: 'business_type_master.create',
            label: 'Customer type',
        );
    }

    /**
     * Region quick-create from inside the addresses repeater.
     * The trait can't model this case (per-row search + caller-chosen kind),
     * so this is the explicit handler.
     */
    public function createRegionForAddress(int $index, string $kind): void
    {
        $this->authorize('region_master.create');

        if (! in_array($kind, RegionMaster::kinds(), true)) {
            return;
        }
        if (! isset($this->addresses[$index])) {
            return;
        }

        $name = strtoupper(trim((string) ($this->addresses[$index]['regionSearch'] ?? '')));
        if ($name === '') {
            return;
        }

        // Region uniqueness is (kind, parent_id, name). Quick-add creates
        // top-level orphans — user can attach a parent later in RegionMaster.
        $region = RegionMaster::firstOrCreate(
            ['kind' => $kind, 'parent_id' => null, 'name' => $name],
            ['is_active' => true],
        );

        $this->addresses[$index]['region_id'] = $region->id;
        $this->addresses[$index]['regionSearch'] = '';

        Flux::toast(
            text: ucfirst($kind).' "'.$region->name.'" added.',
            variant: 'success',
        );
    }

    /**
     * Remove a staged or persisted KYC file. For persisted files, the actual
     * delete from storage happens at save() time so users can still cancel.
     */
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

    public function removeGstCertificateFile(): void
    {
        $this->gst_certificate_file = null;
        $this->gst_certificate_file_path = null;
        $this->gst_certificate_file_name = null;
    }

    public function addAddress(): void
    {
        $this->addresses[] = $this->blankAddress(false);
    }

    public function removeAddress(int $index): void
    {
        if (! isset($this->addresses[$index])) {
            return;
        }

        $wasPrimary = $this->addresses[$index]['is_primary'] ?? false;
        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);

        if ($wasPrimary && ! empty($this->addresses)) {
            $this->addresses[0]['is_primary'] = true;
        }
    }

    public function setPrimary(int $index): void
    {
        foreach ($this->addresses as $i => $row) {
            $this->addresses[$i]['is_primary'] = $i === $index;
        }
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'customer_master.update' : 'customer_master.create');

        // Normalise formatted inputs before validation: uppercase the alphanumeric
        // codes, and strip the mask spaces from Aadhaar / phone (their masks bind the
        // separators into the value, which would otherwise fail the length rules).
        $this->pan = filled($this->pan) ? strtoupper(trim($this->pan)) : null;
        $this->gstin = filled($this->gstin) ? strtoupper(trim($this->gstin)) : null;
        $this->aadhar = filled($this->aadhar) ? preg_replace('/\D/', '', $this->aadhar) : null;
        $this->phone = preg_replace('/\D/', '', (string) $this->phone);
        $this->alternate_phone = filled($this->alternate_phone) ? preg_replace('/\D/', '', $this->alternate_phone) : null;

        $this->addresses = array_values(array_filter(
            $this->addresses,
            fn ($a) => ! empty($a['label']) || ! empty($a['address_line']) || ! empty($a['region_id']),
        ));

        if (! empty($this->addresses)) {
            $primaryCount = collect($this->addresses)->where('is_primary', true)->count();
            if ($primaryCount === 0) {
                $this->addresses[0]['is_primary'] = true;
            } elseif ($primaryCount > 1) {
                $firstPrimaryIdx = collect($this->addresses)->search(fn ($a) => $a['is_primary'] === true);
                foreach ($this->addresses as $i => $row) {
                    $this->addresses[$i]['is_primary'] = $i === $firstPrimaryIdx;
                }
            }
        }

        $this->validate();
        $addresses = $this->addresses;
        $data = collect($this->validate())->except(['addresses', 'aadhar_file', 'pan_file', 'gst_certificate_file'])->all();

        $skip = ['email', 'secondary_email', 'business_type_id', 'gst_type_id', 'referred_by_customer_id', 'date_of_birth', 'is_active', 'phone', 'alternate_phone', 'aadhar'];
        foreach ($data as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $data[$key] = strtoupper($value);
            }
        }

        $isCreate = $this->editingId === null;
        $aadharFile = $this->aadhar_file;
        $panFile = $this->pan_file;
        $gstCertFile = $this->gst_certificate_file;
        $aadharCleared = $this->aadhar_file_path === null;
        $panCleared = $this->pan_file_path === null;
        $gstCertCleared = $this->gst_certificate_file_path === null;

        $customer = DB::transaction(function () use ($data, $addresses, $aadharFile, $panFile, $gstCertFile, $aadharCleared, $panCleared, $gstCertCleared) {
            if ($this->editingId) {
                $c = CustomerMaster::findOrFail($this->editingId);
                $c->update($data);
            } else {
                $c = CustomerMaster::create($data);
                $this->editingId = $c->id;
            }

            $this->syncAddresses($c, $addresses);
            $this->syncKycFile($c, 'aadhar', $aadharFile, $aadharCleared);
            $this->syncKycFile($c, 'pan', $panFile, $panCleared);
            $this->syncKycFile($c, 'gst_certificate', $gstCertFile, $gstCertCleared);

            return $c;
        });

        Flux::toast(
            text: 'Customer #'.$customer->id.($isCreate ? ' created.' : ' updated.'),
            variant: 'success',
        );

        return redirect()->route('customer-master.index');
    }

    /**
     * Persist a freshly-staged upload, clear a removed file, or leave existing alone.
     * `$type` is 'aadhar' or 'pan'. The original filename is kept so downloads
     * stream as the file the client uploaded.
     */
    protected function syncKycFile(CustomerMaster $customer, string $type, $newFile, bool $clearedByUser): void
    {
        $pathCol = $type.'_file_path';
        $nameCol = $type.'_file_name';
        $existing = $customer->{$pathCol};

        if ($newFile instanceof TemporaryUploadedFile) {
            // Upload (or replace). Delete old then store new.
            if ($existing) {
                Storage::delete($existing);
            }
            $path = $newFile->store("customers/{$customer->id}/{$type}");
            $customer->forceFill([
                $pathCol => $path,
                $nameCol => $newFile->getClientOriginalName(),
            ])->save();

            return;
        }

        if ($clearedByUser && $existing) {
            // User removed the existing file without staging a replacement.
            Storage::delete($existing);
            $customer->forceFill([$pathCol => null, $nameCol => null])->save();
        }
    }

    /**
     * @param  array<int, array{id?: int|null, label?: ?string, address_line?: ?string, region_id?: ?int, is_primary?: bool}>  $rows
     */
    protected function syncAddresses(CustomerMaster $customer, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $payload = [
                'label' => filled($row['label'] ?? null) ? strtoupper(trim($row['label'])) : null,
                'address_line' => filled($row['address_line'] ?? null) ? strtoupper(trim($row['address_line'])) : null,
                'region_id' => $row['region_id'] ?? null,
                'is_primary' => (bool) ($row['is_primary'] ?? false),
            ];

            if (! empty($row['id'])) {
                $existing = $customer->addresses()->whereKey($row['id'])->first();
                if ($existing) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $customer->addresses()->create($payload);
            $keptIds[] = $created->id;
        }

        $customer->addresses()->whereNotIn('id', $keptIds)->delete();
    }

    /** @return array{id: null, label: null, address_line: null, region_id: null, regionSearch: string, is_primary: bool} */
    protected function blankAddress(bool $primary): array
    {
        return [
            'id' => null,
            'label' => null,
            'address_line' => null,
            'region_id' => null,
            'regionSearch' => '',
            'is_primary' => $primary,
        ];
    }

    #[Computed]
    public function referrers()
    {
        return CustomerMaster::query()
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->where('is_active', true)
            ->orderBy('first_name')
            ->limit(500)
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'phone']);
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

    public function render()
    {
        return view('customer-master::edit', [
            'businessTypes' => BusinessTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'gstTypes' => GstTypeMaster::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
