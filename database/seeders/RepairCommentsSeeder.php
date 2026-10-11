<?php

namespace Database\Seeders;

use App\Models\RepairComment;
use App\Models\RepairRequest;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RepairCommentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $repairRequests = RepairRequest::all();

        foreach($repairRequests as $request)
        {
            $count = $request->id % 3 + 1;

            RepairComment::factory()
            ->count($count)
            ->for($request,'repairRequest')
            ->create();
        }
    }
}
