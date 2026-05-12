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
            'ID', 'Code', 'Name', 'Vendor Types', 'Parts Brands', 'Phone', 'Alt Phone',
            'Email', 'Secondary Email',
            'Address', 'Region', 'Pincode',
            'Aadhar', 'Aadhar File', 'PAN', 'PAN File', 'GSTIN',
            'Bank', 'Branch', 'IFSC', 'Account No', 'Account Holder',
            'Credit Days', 'Credit Limit', 'Terms', 'Term Count',
            'Active', 'Notes', 'Created At',
        ];
    }

    public function query(): Builder
    {
        return VendorMaster::query()
            ->with([
                'vendorTypes:id,name',
                'spareBrands:id,name',
                'region.parent.parent.parent',
                'bank:id,name',
                'terms:id,vendor_id,name,value,sort_order',
            ])
            ->orderBy('name');
    }

    /**
     * @param  VendorMaster  $model
     */
    public function row(object $model): array
    {
        $types = $model->vendorTypes->pluck('name')->implode('; ');
        $brands = $model->spareBrands->pluck('name')->implode('; ');
        $terms = $model->terms->map(fn ($t) => $t->name.': '.$t->value)->implode(' | ');

        // Walk the region chain to get the human-readable string and the leaf pincode.
        $regionChain = '';
        $pincode = null;
        if ($model->region) {
            $names = [];
            $node = $model->region;
            $depth = 0;
            while ($node && $depth < 6) {
                $names[] = $node->name;
                if ($node->kind === 'pincode') {
                    $pincode = $node->name;
                }
                $node = $node->parent;
                $depth++;
            }
            $regionChain = implode(', ', $names);
        }

        return [
            $model->id, $model->vendor_code, $model->name,
            $types, $brands,
            $model->phone, $model->alternate_phone,
            $model->email, $model->secondary_email,
            $model->address, $regionChain, $pincode,
            $model->aadhar, $model->aadhar_file_name,
            $model->pan, $model->pan_file_name, $model->gstin,
            $model->bank?->name, $model->bank_branch, $model->ifsc, $model->account_no, $model->account_holder,
            $model->credit_days, $model->credit_limit,
            $terms, $model->terms->count(),
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
