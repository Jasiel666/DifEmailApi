<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

Route::get('/', [EmailController::class, 'showEmailForm'])->name('home');

Route::prefix('email')->group(function () {
    Route::get('/', [EmailController::class, 'showEmailForm'])->name('email.form');
    Route::post('/send', [EmailController::class, 'sendEmail'])->name('email.send');
    Route::get('/auth', [EmailController::class, 'showAuth'])->name('email.auth');
    Route::get('/callback', [EmailController::class, 'callback'])->name('email.callback');
});