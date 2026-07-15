<?php

use App\Http\Controllers\Payment\CalPayCallbackController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/** CalPay browser return URLs (used when COMPLETE/CANCEL point at APP_URL). */
Route::match(['get', 'post'], '/payment/success', function (\Illuminate\Http\Request $request) {
    return app(CalPayCallbackController::class)->browserReturn($request, 'success');
});
Route::match(['get', 'post'], '/payment/cancelled', function (\Illuminate\Http\Request $request) {
    return app(CalPayCallbackController::class)->browserReturn($request, 'cancelled');
});
Route::match(['get', 'post'], '/payment/canceled', function (\Illuminate\Http\Request $request) {
    return app(CalPayCallbackController::class)->browserReturn($request, 'cancelled');
});
