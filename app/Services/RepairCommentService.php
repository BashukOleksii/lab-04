<?php

namespace App\Services;

use App\Models\RepairComment;
use App\Models\RepairRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;

class RepairCommentService
{
    public function paginateFor(RepairRequest $repairRequest): LengthAwarePaginator
    {
        return $repairRequest->repairComments()
            ->newestFirst()
            ->paginate(10, ['*'], 'comments_page');
    }


    public function create(
        RepairRequest $repairRequest,
        array $data,
        User $author
    ): RepairComment {
        $comment = $repairRequest->repairComments()->make([
            'body' => $data['body'],
        ]);

        $comment->author()->associate($author);

        $comment->saveOrFail();

        return $comment;
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
