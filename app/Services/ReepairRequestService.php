<?php

namespace App\Services;

use App\Models\RepairRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class RepairRequestQueryService {

    public function paginate(array $filters): LengthAwarePaginator{

        $sort = $filters['sort'] ?? 'date';
        $direction = $filters['directions'] ?? 'desc';

        $column = match($sort){
            'priority' => 'priority',
            default => 'reported_at'
        };

        $direction = $direction === 'asc ' ? 'asc' : 'desc';

        $query = RepairRequest::query()
        ->with('department')
        ->withCount('repairComments')
        ->filtered($filters);

        $query->orderBy($column, $direction);

        if($sort === 'priority')
            $query->orderBy($sort,$direction);

        $query->orderBy('id',$direction);

        return $query->paginate(20)->appends($filters);

    }
}
