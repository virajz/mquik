<?php

namespace App\Modules\CustomerMaster\Http;

use App\Modules\CustomerMaster\Models\CustomerMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a customer's KYC document (aadhar / pan) from the storage disk
 * as a download with the original filename. Keeps S3 paths private.
 */
class CustomerDocumentController
{
    public function __invoke(Request $request, CustomerMaster $customer, string $type): StreamedResponse|BinaryFileResponse
    {
        abort_unless(in_array($type, ['aadhar', 'pan'], true), 404);

        $path = $customer->{$type.'_file_path'};
        $name = $customer->{$type.'_file_name'};

        abort_if(! $path || ! Storage::exists($path), 404);

        return Storage::download($path, $name ?: basename($path));
    }
}
