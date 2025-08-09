<?php

use Illuminate\Support\Facades\Route;
use Schoolees\Psgc\Http\Controllers\RegionController;
use Schoolees\Psgc\Http\Controllers\ProvinceController;
use Schoolees\Psgc\Http\Controllers\CityController;
use Schoolees\Psgc\Http\Controllers\BarangayController;

Route::prefix(config('psgc.route_prefix', 'psgc'))->group(function () {
    Route::controller(RegionController::class)->prefix('/regions')->group(fn () => Route::get('', 'show'));
    Route::controller(ProvinceController::class)->prefix('/provinces')->group(fn () => Route::get('', 'show'));
    Route::controller(CityController::class)->prefix('/cities')->group(fn () => Route::get('', 'show'));
    Route::controller(BarangayController::class)->prefix('/barangays')->group(fn () => Route::get('', 'show'));
});
