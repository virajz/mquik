<?php

use App\Modules\MissingDocumentReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:missing_document_reason_master.view'])->group(function () {
    Route::get('/missing-document-reason-master', Index::class)->name('missing-document-reason-master.index');
});
