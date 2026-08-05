<?php

use App\Modules\RBAC\Http\Controllers\PermissionController;
use App\Modules\RBAC\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:super-admin'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::post('/permissions', [PermissionController::class, 'store']);
});
