<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\RepairRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairCommentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_store_sets_correct_foreign_key(): void
    {
        $department = Department::factory()->create();
        $repair = RepairRequest::factory()->for($department, 'department')->create();

        $this->post(route('repair-requests.comments.store', $repair), [
            'body' => 'Перевірено кабель живлення',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('repair_comments', [
            'repair_request_id' => $repair->id,
            'body' => 'Перевірено кабель живлення',
        ]);
    }

    public function test_comment_destroy_rejects_wrong_parent_and_preserves_record(): void
    {
        $parent = RepairRequest::factory()->create();
        $other = RepairRequest::factory()->create();
        $comment = $other->repairComments()->create(['body' => 'Чужий коментар']);

        $this->delete(route('repair-requests.comments.destroy', [$parent, $comment]))
            ->assertNotFound();

        $this->assertDatabaseHas('repair_comments', ['id' => $comment->id, 'body' => 'Чужий коментар']);
    }

    public function test_comment_update_rejects_wrong_parent(): void
    {
        $parent = RepairRequest::factory()->create();
        $other = RepairRequest::factory()->create();
        $comment = $other->repairComments()->create(['body' => 'Оригінальний коментар']);

        $this->patch(route('repair-requests.comments.update', [$parent, $comment]), [
            'body' => 'Підміна',
        ])->assertNotFound();

        $this->assertDatabaseHas('repair_comments', [
            'id' => $comment->id, 'body' => 'Оригінальний коментар',
        ]);
    }

    public function test_comment_can_be_updated_and_deleted_with_correct_parent(): void
    {
        $repair = RepairRequest::factory()->create();
        $comment = $repair->repairComments()->create(['body' => 'Розпочато']);

        $this->patch(route('repair-requests.comments.update', [$repair, $comment]), [
            'body' => 'Ремонт завершено',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('repair_comments', ['id' => $comment->id, 'body' => 'Ремонт завершено']);

        $this->delete(route('repair-requests.comments.destroy', [$repair, $comment]))
            ->assertRedirect();
        $this->assertDatabaseMissing('repair_comments', ['id' => $comment->id]);
        $this->assertDatabaseHas('repair_requests', ['id' => $repair->id]);
    }

    public function test_comment_validation_rejects_empty_text(): void
    {
        $repair = RepairRequest::factory()->create();
        $this->post(route('repair-requests.comments.store', $repair), [
            'body' => '',
        ])->assertSessionHasErrors('body');
        $this->assertDatabaseCount('repair_comments', 0);
    }

    public function test_comments_are_paginated_ten_per_page_for_one_repair(): void
    {
        $repair = RepairRequest::factory()->create();
        $other = RepairRequest::factory()->create();
        for ($i = 1; $i <= 11; $i++) {
            $repair->repairComments()->create(['body' => 'Коментар №'.$i]);
        }
        $other->repairComments()->create(['body' => 'Лише для іншої заявки']);

        $this->get(route('repair-requests.show', $repair))
            ->assertOk()
            ->assertViewHas('comments', fn ($items) => $items->count() === 10
                && $items->total() === 11 && $items->lastPage() === 2)
            ->assertDontSee('Лише для іншої заявки');

        $this->get(route('repair-requests.show', [
            'repairRequest' => $repair->id, 'comments_page' => 2,
        ]))->assertOk()
            ->assertViewHas('comments', fn ($items) => $items->count() === 1
                && $items->currentPage() === 2);
    }
}
