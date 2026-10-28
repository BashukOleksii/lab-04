<?php

namespace Database\Factories;

use App\Models\RepairComment;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairComment>
 */
class RepairCommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'repair_request_id' => RepairRequest::factory(),
            'author_id' => User::factory(),

            'body' => fake()->randomElement([
                'Отримано повідомлення про несправність.',
                'Проведено первинну діагностику.',
                'Очікуємо необхідні комплектуючі.',
                'Проблему передано спеціалісту.',
                'Розпочато ремонт обладнання.',
                'Перевірено роботу обладнання.',
                'Необхідна додаткова перевірка.',
            ]),
        ];
    }
}
