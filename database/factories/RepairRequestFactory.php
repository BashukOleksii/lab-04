<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\RepairRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairRequest>
 */
class RepairRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
   public function definition(): array
    {
        
        return [
            'department_id' => Department::factory(),
            'subject' => fake()->randomElement([
                'Не працює комп’ютер',
                'Несправний принтер',
                'Не працює кондиціонер',
                'Пошкоджено монітор',
                'Не працює мережеве обладнання',
                'Потрібна заміна кабелю',
                'Несправна клавіатура',
                'Не вмикається ноутбук'
            ]),
            'priority' => fake()->numberBetween(1,3),
            'status' => 'open',
            'reported_at' => fake()->dateTimeBetween('-90 days','now'),
            'cost' => null,
            'urgent' => fake()->boolean(25),
            'completed_at' => true,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'cost' => null,
            'completed_at' => null
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'cost' => null,
            'completed_at' => null
        ]);
    }

    public function closes(): static{
        return $this->state(
            function (array $attributes){

            $reportedAt = Carbon::parse($attributes['reported_at']);

            return [
                'status' => 'closed',
                'cost' => fake()->randomFloat(2,0,25000),
                'completed_at' => fake()->dateTimeBetween(
                    $reportedAt,
                    'now'
                )
            ];
        });
    }
}
 