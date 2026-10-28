<?php

namespace App\Services;

use App\Models\RepairRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;
use App\Models\User;

class RepairRequestService
{
    public function __construct(
        private readonly RepairDefectFileService $files,
        private readonly RepairCommentService $comments
    ) {}


    public function create(
        array $data,
        User $owner,
        ?UploadedFile $file = null
    ): RepairRequest {
        $initialComment = trim(
            (string) ($data['initial_comment'] ?? '')
        );

        unset(
            $data['initial_comment'],
            $data['defect_file'],
            $data['defect_path'],
            $data['owner_id'],
            $data['author_id'],
            $data['review_file_path']
        );

        if (! in_array(
            $data['status'] ?? null,
            ['open', 'in_progress'],
            true
        )) {
            throw new \InvalidArgumentException(
                'Недопустимий початковий статус заявки.'
            );
        }

        $newPath = $file !== null
            ? $this->files->store($file)
            : null;

        if ($newPath !== null) {
            $data['defect_path'] = $newPath;
        }

        try {
            return DB::transaction(
                function () use ($owner, $data, $initialComment): RepairRequest {
                    $repairRequest = $owner
                        ->ownedRequests()
                        ->create($data);

                    if ($initialComment !== '') {
                        $this->comments->create(
                            $repairRequest,
                            ['body' => $initialComment],
                            $owner
                        );
                    }

                    return $repairRequest;
                }
            );
        } catch (Throwable $exception) {
            $this->cleanupFailedUpload($newPath);

            throw $exception;
        }
    }


    public function update(
        RepairRequest $repairRequest,
        array $data,
        ?UploadedFile $file = null
    ): void {
        unset($data['defect_file'], $data['defect_path'], $data['initial_comment']);

        $oldPath = $repairRequest->defect_path;
        $newPath = $file !== null ? $this->files->store($file) : null;

        if ($newPath !== null) {
            $data['defect_path'] = $newPath;
        }

        try {
            DB::transaction(function () use ($repairRequest, $data): void {
                $repairRequest->update($data);
            });
        } catch (Throwable $exception) {
            $this->cleanupFailedUpload($newPath);
            throw $exception;
        }

        if ($newPath !== null && $newPath !== $oldPath) {
            $this->files->delete($oldPath);
        }
    }

    public function delete(RepairRequest $repairRequest): void
    {
        $oldPath = $repairRequest->defect_path;

        DB::transaction(function () use ($repairRequest): void {
            $repairRequest->delete();
        });

        $this->files->delete($oldPath);
    }

    private function cleanupFailedUpload(?string $path): void
    {
        if ($path === null) {
            return;
        }

        try {
            $this->files->delete($path);
        } catch (Throwable $cleanupError) {
            report($cleanupError);
        }
    }
}
