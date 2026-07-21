<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clear expired barcode PDFs every hour.
Schedule::command('barcode:clear-generated-pdfs')->hourly();

// Retire document collections past their retention window (soft delete).
Schedule::command('documents:apply-retention')->dailyAt('02:00');
