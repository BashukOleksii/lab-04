<?php

namespace App\Policies;

use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RepairRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RepairRequest $repairRequest): bool
    {
        return $this->isOwner($user, $repairRequest)
            || $repairRequest->participatedUsers()
            ->where('users.id', $user->getKey())
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RepairRequest $repairRequest): bool
    {
        return !$this->isLocked($repairRequest)
            &&
            (
                $this->isOwner($user, $repairRequest)
                || $this->hasRole($user, $repairRequest, 'editor')
            );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RepairRequest $repairRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RepairRequest $repairRequest): bool
    {
        return $this->isOwner($user, $repairRequest);
    }

    public function manageParticipants(User $user, RepairRequest $repairRequest): bool
    {
        return $this->isOwner($user, $repairRequest);
    }

    public function close(User $user, RepairRequest $repairRequest): bool
    {
        return !$this->isLocked($repairRequest)
            && $repairRequest->cost !== null
            && $repairRequest->completed_at !== null
            && $this->hasRole($user, $repairRequest, 'reviewer');
    }

    public function downloadPrivate(User $user, RepairRequest $repairRequest): bool
    {
        return $this->isOwner($user, $repairRequest)
            || $this->hasRole($user, $repairRequest, 'viewer')
            || $this->hasRole($user, $repairRequest, 'reviewer');
    }

    private function isOwner(User $user, RepairRequest $repair)
    {
        return (int) $user->getKey() === (int) $repair->owner_id;
    }

    private function isLocked(RepairRequest $repair)
    {
        return $repair->status === 'closed';
    }

    private function hasRole(
        User $user,
        RepairRequest $request,
        string $role
    ) {
        return $request->participatedUsers()
            ->where('users.id', $user->getKey())
            ->wherePivot('role', $role)
            ->exists();
    }
}
