<?php

namespace Database\Factories;

use App\Models\RepairComment;
use App\Models\RepairRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairComment>
 */
class RepairCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public function definition(): array
    {
        return [
            'repair_request_id' => RepairRequest::factory(),
            'body' => fake()->randomElement([
                'Отримано повідомлення про несправність.',
                'Проведено первинну діагностику.',
                'Очікуємо необхідні комплектуючі.',
                'Проблему передано спеціалісту.',
                'Розпочато ремонт обладнання.',
                'Перевірено роботу обладнання.',
                'Необхідна додаткова перевірка.',
            ])
        ];
    }
}
