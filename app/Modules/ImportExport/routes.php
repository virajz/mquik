<?php

use App\Modules\ImportExport\Models\Export;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(['auth', 'verified'])->group(function () {
    // Secure download — only the owner can fetch their export, and only before it expires
    Route::get('/exports/{export}/download', function (Export $export) {
        abort_unless($export->user_id === auth()->id(), 403);
        abort_unless($export->status === 'completed', 404, 'Export not ready');
        abort_if($export->expires_at && $export->expires_at->isPast(), 410, 'Export expired');
        abort_unless($export->file_path && Storage::disk('local')->exists($export->file_path), 404);

        return Storage::disk('local')->download(
            $export->file_path,
            $export->file_name,
            ['Content-Type' => 'text/csv'],
        );
    })->name('exports.download');
});
