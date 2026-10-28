<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadRepairRequestRequest;
use App\Http\Requests\StoreRepairRequestRequest;
use App\Http\Requests\UpdateRepairRequestRequest;
use App\Models\RepairRequest;
use App\Services\DepartmentService;
use App\Services\RepairCommentService;
use App\Services\RepairDefectFileService;
use App\Services\RepairRequestQueryService;
use App\Services\RepairRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\RepairComment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class RepairRequestController extends Controller
{
    public function index(
        ReadRepairRequestRequest $request,
        RepairRequestQueryService $queryService,
        DepartmentService $departmentService
    ): View {
        $filters = $request->validated();

        /** @var \App\Models\User $user */
        $user = $request->user();

        $repairs = $queryService->paginate($filters, $user);
        $departments = $departmentService->options();

        $departments = $departmentService->options();

        return view('repair-requests.index', compact('repairs', 'departments', 'filters'));
    }

    public function create(DepartmentService $departmentsService): View
    {
        $departments = $departmentsService->options();

        return view('repair-requests.create', compact('departments'));
    }


    public function store(
        StoreRepairRequestRequest $request,
        RepairRequestService $service
    ): RedirectResponse {
        /** @var \App\Models\User $owner */
        $owner = $request->user();

        $repairRequest = $service->create(
            $request->safe()->except(['defect_file']),
            $owner,
            $request->file('defect_file')
        );

        return redirect()
            ->route('repair-requests.show', $repairRequest)
            ->with('success', 'Заявку успішно створено.');
    }


    public function show(
        RepairRequest $repairRequest,
        RepairCommentService $commentsService,
        RepairDefectFileService $files
    ): View {
        return view(
            'repair-requests.show',
            $this->detailsData($repairRequest, $commentsService, $files)
        );
    }

    public function edit(
        RepairRequest $repairRequest,
        RepairCommentService $commentsService,
        RepairDefectFileService $files,
        DepartmentService $departmentsService
    ): View {
        return view('repair-requests.edit', [
            ...$this->detailsData($repairRequest, $commentsService, $files),
            'departments' => $departmentsService->options(),
        ]);
    }

    public function update(
        UpdateRepairRequestRequest $request,
        RepairRequest $repairRequest,
        RepairRequestService $service
    ): RedirectResponse {
        $service->update(
            $repairRequest,
            $request->safe()->except(['defect_file']),
            $request->file('defect_file')
        );

        return redirect()
            ->route('repair-requests.show', $repairRequest)
            ->with('success', 'Заявку успішно оновлено.');
    }

    public function destroy(
        RepairRequest $repairRequest,
        RepairRequestService $service
    ): RedirectResponse {
        $service->delete($repairRequest);

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


    private function detailsData(
        RepairRequest $repairRequest,
        RepairCommentService $commentsService,
        RepairDefectFileService $files
    ): array {
        $repairRequest->load([
            'department',
            'owner',
            'participatedUsers',
        ]);

        $comments = $commentsService->paginateFor($repairRequest);

        $comments->getCollection()->load('author');

        foreach ($comments as $comment) {
            /** @var RepairComment $comment */
            $comment->setRelation(
                'repairRequest',
                $repairRequest
            );
        }

        $usersForParticipants = Gate::allows(
            'manageParticipants',
            $repairRequest
        )
            ? User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            : collect();

        return [
            'repairRequest' => $repairRequest,
            'comments' => $comments,
            'usersForParticipants' => $usersForParticipants,
            'fileUrl' => $files->url($repairRequest->defect_path),
            'fileIsPdf' => $files->isPdf($repairRequest->defect_path),
        ];
    }
}
