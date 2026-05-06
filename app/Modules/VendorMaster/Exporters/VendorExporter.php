<?php

namespace App\Modules\VendorMaster\Exporters;

use App\Modules\ImportExport\Contracts\Exportable;
use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Database\Eloquent\Builder;

class VendorExporter implements Exportable
{
    public function label(): string
    {
        return 'Vendors';
    }

    public function headers(): array
    {
        return [
            'ID', 'Code', 'Name', 'Vendor Type', 'Phone', 'Alt Phone', 'Email',
            'Address', 'City', 'State', 'Pincode',
            'PAN', 'GSTIN',
            'Bank', 'Branch', 'IFSC', 'Account No', 'Account Holder',
            'Credit Days', 'Credit Limit', 'Payment Terms',
            'Active', 'Notes', 'Created At',
        ];
    }

    public function query(): Builder
    {
        return VendorMaster::query()->with(['vendorType:id,name'])->orderBy('name');
    }

    /**
     * @param  VendorMaster  $model
     */
    public function row(object $model): array
    {
        return [
            $model->id, $model->vendor_code, $model->name,
            $model->vendorType?->name,
            $model->phone, $model->alternate_phone, $model->email,
            $model->address, $model->city, $model->state, $model->pincode,
            $model->pan, $model->gstin,
            $model->bank_name, $model->bank_branch, $model->ifsc, $model->account_no, $model->account_holder,
            $model->credit_days, $model->credit_limit, $model->payment_terms,
            $model->is_active ? 'YES' : 'NO',
            $model->notes,
            $model->created_at?->toIso8601String(),
        ];
    }

    public function fileName(): string
    {
        return 'vendors';
    }
}
