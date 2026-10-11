<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReadDepartmentRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(ReadDepartmentRequest $request): View
    {
        $filters = $request->validated();
        $search = trim((string) ($filters['search'] ?? ''));

        $departments = Department::query()
            ->withCount('repairRequests')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('departments.index', compact('departments', 'filters'));
    }

    public function create(): View
    {
        return view('departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

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
        Department $department
    ): RedirectResponse {
        $department->update($request->validated());

        return redirect()
            ->route('departments.show', $department)
            ->with('success', 'Департамент успішно оновлено.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->repairRequests()->exists()) {
            return redirect()
                ->route('departments.index')
                ->with('error', 'Не можна видалити департамент, до якого прив’язані заявки.');
        }

        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Департамент успішно видалено.');
    }
}
