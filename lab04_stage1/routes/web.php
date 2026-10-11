<?php

use App\Http\Controllers\RepairRequestController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/repair-requests');

Route::get('repair-requests/{repairRequest}/download', [RepairRequestController::class, 'download'])
    ->name('repair-requests.download');

Route::resource('repair-requests', RepairRequestController::class);
