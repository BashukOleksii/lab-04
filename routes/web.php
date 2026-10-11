<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\RepairCommentController;
use App\Http\Controllers\RepairRequestController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/repair-requests');

Route::get(
    'repair-requests/{repairRequest}/download',
    [RepairRequestController::class, 'download']
)->name('repair-requests.download');

Route::resource('repair-requests', RepairRequestController::class)
    ->parameters(['repair-requests' => 'repairRequest']);

Route::resource('repair-requests.comments', RepairCommentController::class)
    ->only(['store', 'update', 'destroy'])
    ->parameters([
        'repair-requests' => 'repairRequest',
        'comments' => 'repairComment',
    ]);

Route::resource('departments', DepartmentController::class);
