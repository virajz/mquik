<?php

namespace App\Modules\VendorMaster\Http;

use App\Modules\VendorMaster\Models\VendorMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a vendor's KYC document (aadhar / pan) from the storage disk
 * as a download with the original filename. Keeps S3 paths private.
 */
class VendorDocumentController
{
    public function __invoke(Request $request, VendorMaster $vendor, string $type): StreamedResponse|BinaryFileResponse
    {
        abort_unless(in_array($type, ['aadhar', 'pan'], true), 404);

        $path = $vendor->{$type.'_file_path'};
        $name = $vendor->{$type.'_file_name'};

        abort_if(! $path || ! Storage::exists($path), 404);

        return Storage::download($path, $name ?: basename($path));
    }
}
