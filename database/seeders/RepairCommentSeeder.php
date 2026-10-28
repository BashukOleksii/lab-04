<?php

namespace Database\Seeders;

use App\Models\RepairComment;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class RepairCommentSeeder extends Seeder
{
    public function run(): void
    {
        $editor = User::query()
            ->where('email', 'editor@example.test')
            ->firstOrFail();

        $texts = [
            'Отримано повідомлення про несправність.',
            'Проведено первинну діагностику.',
            'Виявлено причину несправності.',
            'Підготовлено результати перевірки.',
        ];

        RepairRequest::query()
            ->where('subject', 'like', 'DEMO-%')
            ->with('owner')
            ->chunkById(100, function ($requests) use ($editor, $texts): void {
                foreach ($requests as $repair) {
                    foreach ($texts as $index => $text) {
                        $body = sprintf(
                            '[DEMO %d] %s',
                            $index + 1,
                            $text
                        );

                        if ($repair->repairComments()
                            ->where('body', $body)
                            ->exists()
                        ) {
                            continue;
                        }

                        $author = $index % 2 === 0
                            ? $repair->owner
                            : $editor;

                        RepairComment::factory()
                            ->for($repair, 'repairRequest')
                            ->for($author, 'author')
                            ->create([
                                'body' => $body,
                            ]);
                    }
                }
            });
    }
}
