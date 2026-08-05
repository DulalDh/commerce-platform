<?php

use App\Modules\Tenants\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::post('/tenants', [TenantController::class, 'store']);
