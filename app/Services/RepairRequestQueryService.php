<?php

namespace App\Services;

use App\Models\RepairRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RepairRequestQueryService {

     public function paginate(array $filters): LengthAwarePaginator
    {
        $sort = $filters['sort'] ?? 'date';

        $direction = ($filters['direction'] ?? 'desc') === 'asc'
            ? 'asc'
            : 'desc';

        $query = RepairRequest::query()
            ->with('department')
            ->withCount('repairComments')
            ->filtered($filters);

        if ($sort === 'priority') {
            $query->orderBy('priority', $direction)
                ->orderBy('reported_at', $direction);
        } else {
            $query->orderBy('reported_at', $direction);
        }

        $query->orderBy('id', $direction);

        // Пагінація з передачею перевірених фільтрів.
        return $query
            ->paginate(20)
            ->appends($filters);
    }
}
