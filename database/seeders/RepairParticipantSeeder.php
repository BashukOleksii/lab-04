<?php

namespace Database\Seeders;

use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class RepairParticipantSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->whereIn('email', [
                'viewer@example.test',
                'editor@example.test',
                'reviewer@example.test',
            ])
            ->pluck('id', 'email');

        if ($users->count() !== 3) {
            throw new \RuntimeException(
                'Не знайдено тестових користувачів для ролей.'
            );
        }

        RepairRequest::query()
            ->where('subject', 'like', 'DEMO-%')
            ->chunkById(100, function ($requests) use ($users): void {
                foreach ($requests as $repair) {
                    $repair->participatedUsers()
                        ->syncWithoutDetaching([
                            $users['viewer@example.test'] => [
                                'role' => 'viewer',
                            ],
                            $users['editor@example.test'] => [
                                'role' => 'editor',
                            ],
                            $users['reviewer@example.test'] => [
                                'role' => 'reviewer',
                            ],
                        ]);

                    if (str_starts_with($repair->subject, 'DEMO-01')) {
                        $repair->participatedUsers()
                            ->syncWithoutDetaching([
                                $repair->owner_id => [
                                    'role' => 'reviewer',
                                ],
                            ]);
                    }
                }
            });
    }
}
