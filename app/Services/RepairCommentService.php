<?php

namespace App\Services;

use App\Models\RepairComment;
use App\Models\RepairRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class RepairCommentService
{
    public function paginateFor(RepairRequest $repairRequest): LengthAwarePaginator
    {
        return $repairRequest->repairComments()
            ->newestFirst()
            ->paginate(10, ['*'], 'comments_page');
    }

    public function create(RepairRequest $repairRequest, array $data): RepairComment
    {
        return $repairRequest->repairComments()->create($data);
    }

    public function update(
        RepairRequest $repairRequest,
        RepairComment $repairComment,
        array $data
    ): void {
        $this->findForRequest($repairRequest, $repairComment)->update($data);
    }

    public function delete(
        RepairRequest $repairRequest,
        RepairComment $repairComment
    ): void {
        $this->findForRequest($repairRequest, $repairComment)->delete();
    }

    private function findForRequest(
        RepairRequest $repairRequest,
        RepairComment $repairComment
    ): RepairComment {
        return $repairRequest->repairComments()
            ->findOrFail($repairComment->getKey());
    }
}
