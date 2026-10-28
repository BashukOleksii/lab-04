<?php

namespace App\Http\Controllers;

use App\Models\RepairRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CloseRepairRequestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RepairRequest $repairRequest): RedirectResponse
    {
        DB::transaction(function () use ($repairRequest) {

            /** @var RepairRequest $repair*/
            $repair = RepairRequest::query()
                ->whereKey($repairRequest->getKey())
                ->lockForUpdate()
                ->findOrFail();

            Gate::authorize('close', $repair);

            $repair->update([
                'state' => 'closed'
            ]);
        });

        return redirect()
            ->route('repair-requests.show', $repairRequest)
            ->with('success', 'Заявку успішно закрито');
    }
}
