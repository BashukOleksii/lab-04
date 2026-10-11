<?php

namespace App\Services;

use App\Models\RepairRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class RepairRequestQueryService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return RepairRequest::query()
            ->with('department')
            ->withCount('repairComments')
            ->filtered($filters)
            ->sorted(
                $filters['sort'] ?? 'date',
                $filters['direction'] ?? 'desc'
            )
            ->paginate(20)
            ->appends($filters);
    }
}
