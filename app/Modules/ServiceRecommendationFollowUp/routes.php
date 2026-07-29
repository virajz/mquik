<?php

use App\Modules\ServiceRecommendationFollowUp\Livewire\Edit;
use App\Modules\ServiceRecommendationFollowUp\Livewire\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/service-recommendation-follow-up', Index::class)
        ->middleware('can:service_recommendation_follow_up.view')->name('service-recommendation-follow-up.index');
    Route::get('/service-recommendation-follow-up/create', Edit::class)
        ->middleware('can:service_recommendation_follow_up.create')->name('service-recommendation-follow-up.create');
    Route::get('/service-recommendation-follow-up/{serviceRecommendationFollowUp}/edit', Edit::class)
        ->middleware('can:service_recommendation_follow_up.update')->name('service-recommendation-follow-up.edit');
});
