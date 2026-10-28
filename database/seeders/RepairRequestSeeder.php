<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class RepairRequestSeeder extends Seeder
{
    public function run(): void
    {
        $departments = Department::query()
            ->orderBy('id')
            ->get();

        $owners = User::query()
            ->whereIn('email', [
                'owner1@example.test',
                'owner2@example.test',
                'owner3@example.test',
            ])
            ->orderBy('email')
            ->get();

        if ($departments->isEmpty() || $owners->count() !== 3) {
            throw new \RuntimeException(
                'Спочатку потрібно запустити DepartmentSeeder і UserSeeder.'
            );
        }

        $subjects = [
            'Не працює комп’ютер',
            'Несправний принтер',
            'Пошкоджено монітор',
            'Не працює кондиціонер',
            'Заміна мережевого обладнання',
        ];

        for ($i = 0; $i < 40; $i++) {
            $subject = sprintf(
                'DEMO-%02d | %s',
                $i + 1,
                $subjects[$i % count($subjects)]
            );

            if (RepairRequest::query()
                ->where('subject', $subject)
                ->exists()
            ) {
                continue;
            }

            $department = $departments[$i % $departments->count()];
            $owner = $owners[$i % $owners->count()];

            $factory = RepairRequest::factory()
                ->for($department, 'department')
                ->for($owner, 'owner')
                ->state([
                    'subject' => $subject,
                ]);

            $factory = match ($i % 4) {
                0 => $factory->readyToClose(),
                1 => $factory->open(),
                2 => $factory->inProgress(),
                3 => $factory->closed(),
            };

            $factory->create();
        }
    }
}
