<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadDepartmentRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(
        ReadDepartmentRequest $request,
        DepartmentService $service
    ): View {
        $filters = $request->validated();
        $departments = $service->paginate($filters);

        return view('departments.index', compact('departments', 'filters'));
    }

    public function create(): View
    {
        return view('departments.create');
    }

    public function store(
        StoreDepartmentRequest $request,
        DepartmentService $service
    ): RedirectResponse {
        $department = $service->create($request->validated());

        return redirect()
            ->route('departments.show', $department)
            ->with('success', 'Департамент успішно створено.');
    }

    public function show(Department $department): View
    {
        $department->loadCount('repairRequests');

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        return view('departments.edit', compact('department'));
    }

    public function update(
        UpdateDepartmentRequest $request,
        Department $department,
        DepartmentService $service
    ): RedirectResponse {
        $service->update($department, $request->validated());

        return redirect()
            ->route('departments.show', $department)
            ->with('success', 'Департамент успішно оновлено.');
    }

    public function destroy(
        Department $department,
        DepartmentService $service
    ): RedirectResponse {
        if (! $service->delete($department)) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'Не можна видалити департамент, до якого прив’язані заявки.');
        }

        return redirect()
            ->route('departments.index')
            ->with('success', 'Департамент успішно видалено.');
    }
}
