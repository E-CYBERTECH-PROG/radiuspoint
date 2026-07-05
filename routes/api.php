<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;

// The endpoint Safaricom Daraja API will ping upon successful PIN entry
Route::post('/mpesa/callback', [BillingController::class, 'handleCallback'])->name('api.mpesa.callback');