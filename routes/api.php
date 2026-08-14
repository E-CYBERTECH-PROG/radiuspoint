<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PlatformBillingController;

// Safaricom Daraja M-Pesa callback for customer payments
Route::post('/mpesa/callback', [BillingController::class, 'handleCallback'])->name('api.mpesa.callback');

// M-Pesa callback for a tenant paying their RadiusPoint commission invoice
Route::post('/platform-mpesa/callback', [PlatformBillingController::class, 'handleCallback'])->name('api.platform-mpesa.callback');