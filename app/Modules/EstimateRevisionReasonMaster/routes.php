<?php

use App\Modules\EstimateRevisionReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:estimate_revision_reason_master.view'])->group(function () {
    Route::get('/estimate-revision-reason-master', Index::class)->name('estimate-revision-reason-master.index');
});
