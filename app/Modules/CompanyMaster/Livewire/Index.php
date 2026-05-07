<?php

namespace App\Modules\CompanyMaster\Livewire;

use App\Modules\CompanyMaster\Models\CompanyMaster;
use App\Modules\RegionMaster\Models\RegionMaster;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Company')]
class Index extends Component
{
    use WithFileUploads;

    public ?int $id = null;

    public string $legal_name = '';

    public string $trade_name = '';

    public ?string $code = null;

    public ?string $gstin = null;

    public ?string $pan = null;

    public ?string $cin = null;

    public ?string $address = null;

    public ?int $city_id = null;

    public ?int $state_id = null;

    public ?string $pincode = null;

    public ?string $phone = null;

    public ?string $email = null;

    public ?string $website = null;

    public ?string $invoice_footer = null;

    public bool $is_active = true;

    public ?string $logo_path = null;

    public ?string $signature_path = null;

    public ?TemporaryUploadedFile $logo = null;

    public ?TemporaryUploadedFile $signature = null;

    public function mount(): void
    {
        $company = CompanyMaster::instance();
        if (! $company) {
            return;
        }

        $this->id = $company->id;
        $this->legal_name = (string) $company->legal_name;
        $this->trade_name = (string) $company->trade_name;
        $this->code = $company->code;
        $this->gstin = $company->gstin;
        $this->pan = $company->pan;
        $this->cin = $company->cin;
        $this->address = $company->address;
        $this->city_id = $company->city_id;
        $this->state_id = $company->state_id;
        $this->pincode = $company->pincode;
        $this->phone = $company->phone;
        $this->email = $company->email;
        $this->website = $company->website;
        $this->invoice_footer = $company->invoice_footer;
        $this->is_active = (bool) $company->is_active;
        $this->logo_path = $company->logo_path;
        $this->signature_path = $company->signature_path;
    }

    protected function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'gstin' => [
                'nullable', 'string', 'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                Rule::unique('companies', 'gstin')->ignore($this->id),
            ],
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'cin' => ['nullable', 'string', 'max:21'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('kind', 'city')],
            'state_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('kind', 'state')],
            'pincode' => ['nullable', 'string', 'size:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'invoice_footer' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'signature' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        // Handle file uploads (not part of the persisted columns directly).
        unset($validated['logo'], $validated['signature']);

        // Capital typing — skip FK ids, contact fields, paths, and booleans.
        $skip = ['city_id', 'state_id', 'phone', 'email', 'website', 'pincode', 'is_active'];
        foreach ($validated as $key => $value) {
            if (is_string($value) && ! in_array($key, $skip, true)) {
                $validated[$key] = strtoupper($value);
            }
        }

        if ($this->logo) {
            $validated['logo_path'] = $this->logo->store('companies/logos', 'public');
        }

        if ($this->signature) {
            $validated['signature_path'] = $this->signature->store('companies/signatures', 'public');
        }

        $record = CompanyMaster::updateOrCreate(['id' => $this->id], $validated);

        $this->id = $record->id;
        $this->logo_path = $record->logo_path;
        $this->signature_path = $record->signature_path;
        $this->logo = null;
        $this->signature = null;

        Flux::toast(text: 'Company saved.', variant: 'success');
    }

    public function render()
    {
        return view('company-master::index', [
            'cities' => RegionMaster::query()
                ->where('kind', 'city')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'states' => RegionMaster::query()
                ->where('kind', 'state')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
