<?php

namespace App\Modules\CustomerMaster\Exporters;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use App\Modules\ImportExport\Contracts\Exportable;
use Illuminate\Database\Eloquent\Builder;

class CustomerExporter implements Exportable
{
    public function label(): string
    {
        return 'Customers';
    }

    public function headers(): array
    {
        return [
            'ID', 'First Name', 'Middle Name', 'Last Name', 'Company Name', 'Type',
            'Phone', 'Alternate Phone', 'Email', 'Secondary Email',
            'Primary Address', 'Primary Region', 'Primary Pincode', 'Address Count',
            'Referred By (Phone)',
            'Aadhar', 'Aadhar File', 'PAN', 'PAN File', 'Date of Birth',
            'Active', 'Created At',
        ];
    }

    public function query(): Builder
    {
        return CustomerMaster::query()
            ->with([
                'businessType:id,name',
                'primaryAddress.region.parent.parent.parent',
                'addresses:id,customer_id',
                'referredBy:id,phone',
            ])
            ->latest('id');
    }

    /**
     * @param  CustomerMaster  $model
     */
    public function row(object $model): array
    {
        $primary = $model->primaryAddress;
        $pincode = null;
        $node = $primary?->region;
        $depth = 0;
        while ($node && $depth < 6) {
            if ($node->kind === 'pincode') {
                $pincode = $node->name;
                break;
            }
            $node = $node->parent;
            $depth++;
        }

        return [
            $model->id,
            $model->first_name,
            $model->middle_name,
            $model->last_name,
            $model->company_name,
            $model->businessType?->name,
            $model->phone,
            $model->alternate_phone,
            $model->email,
            $model->secondary_email,
            $primary?->address_line,
            $primary?->regionChain(),
            $pincode,
            $model->addresses->count(),
            $model->referredBy?->phone,
            $model->aadhar,
            $model->aadhar_file_name,
            $model->pan,
            $model->pan_file_name,
            $model->date_of_birth?->format('Y-m-d'),
            $model->is_active ? 'YES' : 'NO',
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'customers';
    }
}
