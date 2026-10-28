<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveParticipantRequest;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RepairParticipantController extends Controller
{
    public function store(
        SaveParticipantRequest $request,
        RepairRequest $repairRequest
    ): RedirectResponse {
        $data = $request->validated();

        DB::transaction(function () use ($repairRequest, $data) {
            /** @var RepairRequest $repair */
            $repair = RepairRequest::query()
                ->whereKey($repairRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('manageParticipants', $repair);

            $userId = $data['user_id'];

            $alreadyExists = $repair->participatedUsers()
                ->where('users.id', $userId)
                ->exists();

            if (!$alreadyExists && $userId !== (int) $repair->owner_id) {
                $participatedCount = $repair->participatedUsers()
                    ->where('users.id', '!=', $repair->owner_id)
                    ->count();

                if ($participatedCount >= 6) {
                    throw ValidationException::withMessages([
                        'user_id' => 'Не може бути більше шести учасників для заявки'
                    ]);
                }
            }

            $repair->participatedUsers()->syncWithoutDetaching([
                $userId => ['role' => $data['role']]
            ]);
        });

        return redirect()
            ->route('repair-requests.show', $repairRequest)
            ->with('success', 'Учасника додано до заявки');
    }

    public function destroy(
        RepairRequest $repairRequest,
        User $user
    ): RedirectResponse {
        DB::transaction(
            function () use ($repairRequest, $user) {

                /** @var RepairRequest $repair*/
                $repair = RepairRequest::query()
                    ->whereKey($repairRequest->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                Gate::authorize('manageParticipants', $repair);

                $participatedExists = $repair->participatedUsers()
                    ->where('users.id', $user->getKey())
                    ->exist();

                abort_unless($participatedExists, 404);

                $repair->participatedUsers()->detach($user->getKey());
            }
        );


        return redirect()
            ->route('repair-requests.show', $repairRequest)
            ->with('success', 'Цчасника успішно видлаено із заявки');
    }
}
