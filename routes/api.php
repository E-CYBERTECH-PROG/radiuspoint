<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PlatformBillingController;

// The endpoint Safaricom Daraja API will ping upon successful PIN entry
Route::post('/mpesa/callback', [BillingController::class, 'handleCallback'])->name('api.mpesa.callback');

// Same, but for a tenant paying their own RadiusPoint commission invoice — see
// TenantBillingController::pay() / PlatformBillingController::handleCallback().
Route::post('/platform-mpesa/callback', [PlatformBillingController::class, 'handleCallback'])->name('api.platform-mpesa.callback');