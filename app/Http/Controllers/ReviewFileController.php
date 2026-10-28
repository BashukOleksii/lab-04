<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewFileRequest;
use App\Models\RepairRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

use Illuminate\Filesystem\FilesystemAdapter;


class ReviewFileController extends Controller
{
    public function store(
        StoreReviewFileRequest $request,
        RepairRequest $repairRequest
    ): RedirectResponse {
        $disk = Storage::disk('local');

        $newPath = $request->file('review_file')->store(
            'repair-reviews/' . $repairRequest->getKey(),
            'local'
        );

        if ($newPath === false) {
            throw new RuntimeException(
                'Не вдалося зберегти документ.'
            );
        }

        $oldPath = null;

        try {
            DB::transaction(function () use (
                $repairRequest,
                $newPath,
                &$oldPath
            ): void {
                $repair = RepairRequest::query()
                    ->whereKey($repairRequest->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::authorize('update', $repair);

                $oldPath = $repair->review_file_path;

                $repair->review_file_path = $newPath;
                $repair->saveOrFail();
            });
        } catch (Throwable $exception) {
            try {
                $disk->delete($newPath);
            } catch (Throwable $cleanupError) {
                report($cleanupError);
            }

            throw $exception;
        }

        if ($oldPath !== null && $oldPath !== $newPath) {
            try {
                if (! $disk->delete($oldPath)) {
                    report(new RuntimeException(
                        'Не вдалося видалити старий приватний документ.'
                    ));
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return redirect()
            ->route('repair-requests.show', $repairRequest)
            ->with('success', 'Приватний документ збережено.');
    }

    public function download(RepairRequest $repairRequest): StreamedResponse
    {
        $path = $repairRequest->review_file_path;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        abort_if(
            $path === null || !$disk->exists($path),
            404,
            'Документ не знайдено.'
        );

        return $disk->download(
            $path,
            'review-' . $repairRequest->getKey() . '.pdf'
        );
    }
}
