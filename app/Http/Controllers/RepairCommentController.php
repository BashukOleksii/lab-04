<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRepairCommentRequest;
use App\Http\Requests\UpdateRepairCommentRequest;
use App\Models\RepairComment;
use App\Models\RepairRequest;
use Illuminate\Http\RedirectResponse;

class RepairCommentController extends Controller
{
    public function store(
        StoreRepairCommentRequest $request,
        RepairRequest $repairRequest
    ): RedirectResponse {

        $repairRequest->repairComments()
            ->create($request->validated());

        return back()->with(
            'success',
            'Коментар успішно додано.'
        );
    }

    public function update(
        UpdateRepairCommentRequest $request,
        RepairRequest $repairRequest,
        RepairComment $repairComment
    ): RedirectResponse {

        $comment = $repairRequest->repairComments()
            ->findOrFail($repairComment->getKey());

        $comment->update($request->validated());

        return back()->with(
            'success',
            'Коментар успішно оновлено.'
        );
    }

    public function destroy(
        RepairRequest $repairRequest,
        RepairComment $repairComment
    ): RedirectResponse {

        $comment = $repairRequest->repairComments()
            ->findOrFail($repairComment->getKey());

        $comment->delete();

        return back()->with(
            'success',
            'Коментар успішно видалено.'
        );
    }
}
