<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\RepairCommentController;
use App\Http\Controllers\RepairRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');
});

Route::middleware(['auth', 'active.account'])->group(function () {
    Route::redirect('/', '/repair-requests');

    Route::get(
        'repair-requests/{repairRequest}/download',
        [RepairRequestController::class, 'download']
    )
        ->can('downloadPrivate', 'repairRequest')
        ->name('repair-requests.download');

    Route::resource('repair-requests', RepairRequestController::class)
        ->middlewareFor('index', 'can:viewAny,App\Models\RepairRequest')
        ->middlewareFor('show', 'can:view,repairRequest')
        ->middlewareFor(['create', 'store'], 'can:create,App\Models\RepairRequest')
        ->middlewareFor(['edit', 'update'], 'can:create,repairRequest')
        ->middlewareFor('delete', 'can:create,repairRequest')
        ->parameters(['repair-requests' => 'repairRequest']);

    Route::resource('repair-requests.comments', RepairCommentController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters([
            'repair-requests' => 'repairRequest',
            'comments' => 'repairComment',
        ])
        ->scoped()
        ->middlewareFor('store', 'can:create,App\Models\RepairComment,repairRequest')
        ->middlewareFor('update', 'can:update,repairComment')
        ->middlewareFor('delete', 'can:delete,repairComment');

    Route::resource('departments', DepartmentController::class);

    Route::post('logout', [AuthController::class, 'logout'])
        ->name('logout');
});
