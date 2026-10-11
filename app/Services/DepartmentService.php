<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return Department::query()
            ->searchByName($filters['search'] ?? null)
            ->withCount('repairRequests')
            ->alphabetically()
            ->paginate(10)
            ->appends($filters);
    }

    public function options(): Collection
    {
        return Department::query()
            ->select(['id', 'name'])
            ->alphabetically()
            ->get();
    }

    public function create(array $data): Department
    {
        return Department::create($data);
    }

    public function update(Department $department, array $data): void
    {
        $department->update($data);
    }

    public function delete(Department $department): bool
    {
        if ($department->repairRequests()->exists()) {
            return false;
        }

        $department->delete();

        return true;
    }
}
