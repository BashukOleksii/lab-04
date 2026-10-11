<?php

namespace Database\Seeders;

use App\Models\RepairComment;
use App\Models\RepairRequest;
use Database\Factories\RepairCommentFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            RepairRequestSeeder::class,
            RepairCommentSeeder::class
        ]);
    }
}
