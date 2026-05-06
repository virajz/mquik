<?php

use App\Modules\EnquirySourceMaster\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/enquiry-source-master', Index::class)->name('enquiry-source-master.index');
});
