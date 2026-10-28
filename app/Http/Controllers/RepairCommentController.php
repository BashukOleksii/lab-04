<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRepairCommentRequest;
use App\Http\Requests\UpdateRepairCommentRequest;
use App\Models\RepairComment;
use App\Models\RepairRequest;
use App\Services\RepairCommentService;
use Illuminate\Http\RedirectResponse;

class RepairCommentController extends Controller
{

    public function store(
        StoreRepairCommentRequest $request,
        RepairRequest $repairRequest,
        RepairCommentService $service
    ): RedirectResponse {
        /** @var \App\Models\User $author */
        $author = $request->user();

        $service->create(
            $repairRequest,
            $request->validated(),
            $author
        );

        return back()->with(
            'success',
            'Коментар успішно додано.'
        );
    }


    public function update(
        UpdateRepairCommentRequest $request,
        RepairRequest $repairRequest,
        RepairComment $repairComment,
        RepairCommentService $service
    ): RedirectResponse {
        $service->update($repairRequest, $repairComment, $request->validated());

        return back()->with('success', 'Коментар успішно оновлено.');
    }

    public function destroy(
        RepairRequest $repairRequest,
        RepairComment $repairComment,
        RepairCommentService $service
    ): RedirectResponse {
        $service->delete($repairRequest, $repairComment);

        return back()->with('success', 'Коментар успішно видалено.');
    }
}
