<?php

use App\Http\Controllers\Settings\EmailSignatureController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/email-signatures', [EmailSignatureController::class, 'index'])
        ->name('email-signatures.index');
    Route::post('settings/email-signatures', [EmailSignatureController::class, 'store'])
        ->name('email-signatures.store');
    Route::patch('settings/email-signatures/{email_signature}', [EmailSignatureController::class, 'update'])
        ->name('email-signatures.update');
    Route::delete('settings/email-signatures/{email_signature}', [EmailSignatureController::class, 'destroy'])
        ->name('email-signatures.destroy');
    Route::post('settings/email-signatures/{email_signature}/default', [EmailSignatureController::class, 'makeDefault'])
        ->name('email-signatures.make-default');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');
});
