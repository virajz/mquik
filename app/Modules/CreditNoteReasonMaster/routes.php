<?php

use App\Modules\CreditNoteReasonMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:credit_note_reason_master.view'])->group(function () {
    Route::get('/credit-note-reason-master', Index::class)->name('credit-note-reason-master.index');
});
