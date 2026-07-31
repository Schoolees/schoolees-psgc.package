<?php

use Illuminate\Support\Facades\Route;
use Schoolees\Psgc\Http\Controllers\BarangayController;
use Schoolees\Psgc\Http\Controllers\CityController;
use Schoolees\Psgc\Http\Controllers\ProvinceController;
use Schoolees\Psgc\Http\Controllers\RegionController;

Route::get('regions', [RegionController::class, 'index']);
Route::get('provinces', [ProvinceController::class, 'index']);
Route::get('cities', [CityController::class, 'index']);
Route::get('barangays', [BarangayController::class, 'index']);
