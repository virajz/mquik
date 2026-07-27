<?php

use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;

return [
    'label' => 'Vendor Purchase Inquiries',
    'description' => 'VPI / RFQ — ask a vendor for part rate, brand, delivery, warranty and payment terms; track quotes for comparison.',
    'group' => 'Inventory',
    'icon' => 'clipboard-document-list',
    'permissions' => [
        'vendor_purchase_inquiry.view',
        'vendor_purchase_inquiry.create',
        'vendor_purchase_inquiry.update',
        'vendor_purchase_inquiry.delete',
    ],
    'searchable' => [
        'model' => VendorPurchaseInquiry::class,
        'route' => 'vendor-purchase-inquiry.index',
    ],
];
