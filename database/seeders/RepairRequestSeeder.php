<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\RepairRequest;
use Illuminate\Database\Seeder;

class RepairRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $departments = Department::query()->orderBy('id')->get();

        for($i  = 0; $i < 40; $i++){
            $department = $departments[
                $i % $departments->count()
            ];

            $factory = RepairRequest::factory()->for($department,'department');

            $factory = match($i % 3){
                0 => $factory->closed(),
                1 => $factory->open(),
                2 => $factory->inProgress()
            };

            $factory->create();

        }
    }
}
