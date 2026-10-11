<?php

use App\Http\Controllers\RepairRequestController;
use Illuminate\Support\Facades\Route;

Route::redirect('/','/repair-requests');

Route::get('/repair-requests',
[RepairRequestController::class,'index'])->name('repair-requests.index');