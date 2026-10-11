<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadRepairRequestRequest;
use App\Http\Requests\StoreRepairRequestRequest;
use App\Http\Requests\UpdateRepairRequestRequest;
use App\Models\Department;
use App\Models\RepairRequest;
use App\Services\RepairDefectFileService;
use App\Services\RepairRequestQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RepairRequestController extends Controller
{
    public function index(
        ReadRepairRequestRequest $request,
        RepairRequestQueryService $queryService
    ): View {
        $filters = $request->validated();
        $repairs = $queryService->paginate($filters);
        $departments = $this->departments();

        return view('repair-requests.index', compact('repairs', 'departments', 'filters'));
    }

    public function create(): View
    {
        $departments = $this->departments();

        return view('repair-requests.create', compact('departments'));
    }

    public function store(
        StoreRepairRequestRequest $request,
        RepairDefectFileService $files
    ): RedirectResponse {
        $data = $request->safe()->except(['initial_comment', 'defect_file']);
        $initialComment = trim((string) $request->validated('initial_comment', ''));
        $newPath = null;

        if ($request->hasFile('defect_file')) {
            $newPath = $files->store($request->file('defect_file'));
            $data['defect_path'] = $newPath;
        }

        try {
            // Створення заявки та першого коментаря є однією операцією БД.
            $repairRequest = DB::transaction(function () use ($data, $initialComment) {
                $repairRequest = RepairRequest::create($data);

                if ($initialComment !== '') {
                    $repairRequest->repairComments()->create([
                        'body' => $initialComment,
                    ]);
                }

                return $repairRequest;
            });
        } catch (Throwable $exception) {
            // SQL rollback не видаляє файл: компенсуємо його вручну.
            $this->cleanupAfterFailure($files, $newPath);

            throw $exception;
        }

        return redirect()
            ->route('repair-requests.show', $repairRequest)
            ->with('success', 'Заявку успішно створено.');
    }

    public function show(
        RepairRequest $repairRequest,
        RepairDefectFileService $files
    ): View {
        $repairRequest->load('department');
        $comments = $this->comments($repairRequest);
        $fileUrl = $files->url($repairRequest->defect_path);
        $fileIsPdf = $files->isPdf($repairRequest->defect_path);

        return view('repair-requests.show', compact(
            'repairRequest', 'comments', 'fileUrl', 'fileIsPdf'
        ));
    }

    public function edit(
        RepairRequest $repairRequest,
        RepairDefectFileService $files
    ): View {
        $repairRequest->load('department');
        $departments = $this->departments();
        $comments = $this->comments($repairRequest);
        $fileUrl = $files->url($repairRequest->defect_path);
        $fileIsPdf = $files->isPdf($repairRequest->defect_path);

        return view('repair-requests.edit', compact(
            'repairRequest', 'departments', 'comments', 'fileUrl', 'fileIsPdf'
        ));
    }

    public function update(
        UpdateRepairRequestRequest $request,
        RepairRequest $repairRequest,
        RepairDefectFileService $files
    ): RedirectResponse {
        $data = $request->safe()->except(['defect_file']);
        $oldPath = $repairRequest->defect_path;
        $newPath = null;

        if ($request->hasFile('defect_file')) {
            $newPath = $files->store($request->file('defect_file'));
            $data['defect_path'] = $newPath;
        }

        try {
            DB::transaction(function () use ($repairRequest, $data) {
                $repairRequest->update($data);
            });
        } catch (Throwable $exception) {
            $this->cleanupAfterFailure($files, $newPath);

            throw $exception;
        }

        // Старий файл прибираємо тільки після успішного оновлення БД.
        if ($newPath !== null) {
            $files->delete($oldPath);
        }

        return redirect()
            ->route('repair-requests.show', $repairRequest)
            ->with('success', 'Заявку успішно оновлено.');
    }

    public function destroy(
        RepairRequest $repairRequest,
        RepairDefectFileService $files
    ): RedirectResponse {
        $oldPath = $repairRequest->defect_path;

        // Коментарі видаляються через cascadeOnDelete у міграції.
        DB::transaction(fn () => $repairRequest->delete());
        $files->delete($oldPath);

        return redirect()
            ->route('repair-requests.index')
            ->with('success', 'Заявку видалено.');
    }

    public function download(
        RepairRequest $repairRequest,
        RepairDefectFileService $files
    ): StreamedResponse {
        abort_if($repairRequest->defect_path === null, 404, 'У заявки немає файла.');

        return $files->download($repairRequest->defect_path);
    }

    private function departments()
    {
        return Department::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    private function comments(RepairRequest $repairRequest): LengthAwarePaginator
    {
        return $repairRequest->repairComments()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'comments_page');
    }

    private function cleanupAfterFailure(
        RepairDefectFileService $files,
        ?string $newPath
    ): void {
        try {
            $files->delete($newPath);
        } catch (Throwable $cleanupError) {
            // Не підміняємо вихідний виняток помилкою прибирання.
            report($cleanupError);
        }
    }
}
