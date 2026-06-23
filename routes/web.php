<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::patch('/locale', [LocaleController::class, 'update'])
    ->middleware('throttle:locale-preference')
    ->name('locale.update');
