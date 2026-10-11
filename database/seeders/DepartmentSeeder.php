<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'ІТ-відділ', 'code' => 'IT'],
            ['name' => 'Бухгалтерія', 'code' => 'ACC'],
            ['name' => 'Адміністрація', 'code' => 'ADMIN'],
            ['name' => 'Навчальний відділ', 'code' => 'EDU'],
            ['name' => 'Господарський відділ', 'code' => 'MAINT'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['code' => $department['code']],
                ['name' => $department['name']]
            );
        }
    }
    
}
