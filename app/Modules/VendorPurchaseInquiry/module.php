<?php

use App\Modules\VendorPurchaseInquiry\Models\VendorPurchaseInquiry;

return [
    'label' => 'Vendor Purchase Inquiries',
    'description' => 'VPI / RFQ — ask a vendor for part rate, brand, delivery, warranty and payment terms; track quotes for comparison.',
    'group' => 'Inventory',
    'icon' => 'clipboard-document-list',
    'permissions' => [
        'vendor_purchase_inquiry.view',
        // Seeing WHO supplies a part is procurement work; advisors read the
        // same RFQ for price and grade without it.
        'vendor_purchase_inquiry.manage_vendors',
        'vendor_purchase_inquiry.create',
        'vendor_purchase_inquiry.update',
        'vendor_purchase_inquiry.delete',
    ],
    'searchable' => [
        'model' => VendorPurchaseInquiry::class,
        'route' => 'vendor-purchase-inquiry.index',
    ],
];
