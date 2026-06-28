<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventShareMessageController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PublicEventController;
use App\Http\Controllers\PublicInvitationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::patch('/locale', [LocaleController::class, 'update'])
    ->middleware('throttle:locale-preference')
    ->name('locale.update');

Route::get('/e/{event:public_id}', PublicEventController::class)
    ->name('public.events.show');

Route::get('/e/{event:public_id}/invitation/{token}', PublicInvitationController::class)
    ->name('public.invitations.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:auth-registration')
        ->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:verification-resend')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::patch('/events/{event}/share-message', [EventShareMessageController::class, 'update'])
        ->name('events.share-message.update');

    Route::resource('events.guests', GuestController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('events', EventController::class);
});
