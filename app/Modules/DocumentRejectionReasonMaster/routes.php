<?php

use App\Modules\DocumentRejectionReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:document_rejection_reason_master.view'])->group(function () {
    Route::get('/document-rejection-reason-master', Index::class)->name('document-rejection-reason-master.index');
});
