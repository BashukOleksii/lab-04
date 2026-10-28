<?php

namespace App\Services;

use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class RepairRequestQueryService
{
    public function paginate(
        array $filters,
        User $user
    ): LengthAwarePaginator {
        return RepairRequest::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('owner_id', $user->getKey())
                    ->orWhereHas(
                        'participatedUsers',
                        function (Builder $query) use ($user): void {
                            $query->where(
                                'users.id',
                                $user->getKey()
                            );
                        }
                    );
            })
            ->with(['department', 'owner'])
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
