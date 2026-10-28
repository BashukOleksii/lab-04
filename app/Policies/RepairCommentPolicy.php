<?php

namespace App\Policies;

use App\Models\RepairComment;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RepairCommentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, RepairRequest $repairRequest): bool
    {
        return $user->can('view', $repairRequest);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RepairComment $repairComment): bool
    {
        return $user->can('view', $repairComment->repairRequest);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, RepairRequest $repairRequest): bool
    {
        return $user->can('update', $repairRequest);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RepairComment $repairComment): bool
    {
        return (int) $user->getKey() === (int) $repairComment->author_id
            && $user->can('update', $repairComment->repairRequest);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RepairComment $repairComment): bool
    {
        return $this->update($user, $repairComment);
    }
}
