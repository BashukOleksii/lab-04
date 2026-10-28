<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\RepairComment;
use App\Models\RepairRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class RepairAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    private ?Department $department = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    private function department(): Department
    {
        return $this->department ??= Department::factory()->create();
    }

    private function user(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    private function repair(User $owner, array $attributes = []): RepairRequest
    {
        return RepairRequest::factory()
            ->for($this->department(), 'department')
            ->for($owner, 'owner')
            ->create($attributes);
    }

    private function participant(RepairRequest $repair, User $user, string $role): void
    {
        $repair->participatedUsers()->syncWithoutDetaching([
            $user->getKey() => ['role' => $role],
        ]);
    }

    private function comment(
        RepairRequest $repair,
        User $author,
        string $body = 'Початковий коментар'
    ): RepairComment {
        return RepairComment::factory()
            ->for($repair, 'repairRequest')
            ->for($author, 'author')
            ->create(['body' => $body]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'department_id' => $this->department()->getKey(),
            'subject' => 'Тестова заявка на ремонт',
            'priority' => 2,
            'status' => 'open',
            'urgent' => 0,
            'reported_at' => '2026-10-10T10:00',
            'cost' => null,
            'completed_at' => null,
        ], $overrides);
    }

    private function storedReview(
        RepairRequest $repair,
        string $path = 'repair-reviews/test.pdf',
        string $contents = 'PRIVATE REVIEW CONTENT'
    ): string {
        Storage::disk('local')->put($path, $contents);

        $repair->review_file_path = $path;
        $repair->saveOrFail();

        return $path;
    }

    private function selectQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        try {
            $callback();

            return count(array_filter(
                DB::getQueryLog(),
                static fn(array $query): bool => str_starts_with(
                    strtolower(ltrim($query['query'])),
                    'select'
                )
            ));
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    }

    public function test_guest_cannot_open_repair_index(): void
    {
        $this->get(route('repair-requests.index'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_open_repair_details(): void
    {
        $repair = $this->repair($this->user());

        $this->get(route('repair-requests.show', $repair))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_create_repair(): void
    {
        $this->post(route('repair-requests.store'), $this->payload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('repair_requests', 0);
    }

    public function test_guest_cannot_create_comment(): void
    {
        $repair = $this->repair($this->user());

        $this->post(route('repair-requests.comments.store', $repair), [
            'body' => 'Спроба гостя',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('repair_comments', 0);
    }

    public function test_login_and_registration_pages_are_accessible_to_guests(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    public function test_registration_creates_active_user_with_hashed_password_and_logs_in(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Новий користувач',
            'email' => 'new@example.test',
            'password' => 'SecurePass-2026!',
            'password_confirmation' => 'SecurePass-2026!',
            'is_active' => false,
            'role' => 'reviewer',
        ])->assertRedirect(route('repair-requests.index'));

        $user = User::query()->where('email', 'new@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('SecurePass-2026!', $user->password));
        $this->assertNotSame('SecurePass-2026!', $user->password);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $this->user(['email' => 'taken@example.test']);

        $this->post(route('register.store'), [
            'name' => 'Another',
            'email' => 'taken@example.test',
            'password' => 'SecurePass-2026!',
            'password_confirmation' => 'SecurePass-2026!',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_rejects_wrong_password_confirmation(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Another',
            'email' => 'wrong-confirmation@example.test',
            'password' => 'SecurePass-2026!',
            'password_confirmation' => 'NotTheSame-2026!',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_short_password(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Another',
            'email' => 'short@example.test',
            'password' => '123',
            'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_valid_login_authenticates_user(): void
    {
        $user = $this->user([
            'email' => 'login@example.test',
            'password' => 'ValidPass-2026!',
        ]);

        $this->post(route('login.store'), [
            'email' => 'login@example.test',
            'password' => 'ValidPass-2026!',
        ])->assertRedirect(route('repair-requests.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_does_not_authenticate_user(): void
    {
        $this->user([
            'email' => 'invalid-login@example.test',
            'password' => 'CorrectPass-2026!',
        ]);

        $this->post(route('login.store'), [
            'email' => 'invalid-login@example.test',
            'password' => 'WrongPass-2026!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $this->user([
            'email' => 'inactive-login@example.test',
            'password' => 'CorrectPass-2026!',
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => 'inactive-login@example.test',
            'password' => 'CorrectPass-2026!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_invalidates_authentication(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('repair-requests.index'))->assertRedirect(route('login'));
    }

    public function test_inactive_authenticated_owner_is_blocked(): void
    {
        $owner = $this->user(['is_active' => false]);
        $this->repair($owner);

        $this->actingAs($owner)
            ->get(route('repair-requests.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_owner_can_view_own_repair(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner, ['subject' => 'Заявка власника']);

        $this->actingAs($owner)
            ->get(route('repair-requests.show', $repair))
            ->assertOk()
            ->assertSee('Заявка власника');
    }

    public function test_viewer_can_read_assigned_repair(): void
    {
        $repair = $this->repair($this->user());
        $viewer = $this->user();
        $this->participant($repair, $viewer, 'viewer');

        $this->actingAs($viewer)
            ->get(route('repair-requests.show', $repair))
            ->assertOk();
    }

    public function test_editor_can_read_assigned_repair(): void
    {
        $repair = $this->repair($this->user());
        $editor = $this->user();
        $this->participant($repair, $editor, 'editor');

        $this->actingAs($editor)
            ->get(route('repair-requests.show', $repair))
            ->assertOk();
    }

    public function test_reviewer_can_read_assigned_repair(): void
    {
        $repair = $this->repair($this->user());
        $reviewer = $this->user();
        $this->participant($repair, $reviewer, 'reviewer');

        $this->actingAs($reviewer)
            ->get(route('repair-requests.show', $repair))
            ->assertOk();
    }

    public function test_unrelated_user_cannot_read_repair(): void
    {
        $repair = $this->repair($this->user(), ['subject' => 'Приватна заявка']);

        $this->actingAs($this->user())
            ->get(route('repair-requests.show', $repair))
            ->assertForbidden();
    }

    public function test_unknown_repair_returns_not_found_for_authenticated_user(): void
    {
        $this->actingAs($this->user())
            ->get(route('repair-requests.show', 999999))
            ->assertNotFound();
    }

    public function test_index_contains_only_owned_and_assigned_repairs(): void
    {
        $user = $this->user();
        $owned = $this->repair($user, ['subject' => 'Owned-visible-101']);
        $assigned = $this->repair($this->user(), ['subject' => 'Assigned-visible-202']);
        $unrelated = $this->repair($this->user(), ['subject' => 'Unrelated-hidden-303']);
        $this->participant($assigned, $user, 'viewer');
        $this->participant($owned, $user, 'reviewer');

        $this->actingAs($user)
            ->get(route('repair-requests.index'))
            ->assertOk()
            ->assertViewHas(
                'repairs',
                fn($repairs): bool =>
                $repairs->total() === 2
                    && $repairs->getCollection()->pluck('id')->sort()->values()->all()
                    === collect([$owned->id, $assigned->id])->sort()->values()->all()
            )
            ->assertDontSee($unrelated->subject);
    }

    public function test_index_filters_status_after_access_restriction(): void
    {
        $owner = $this->user();
        $open = $this->repair($owner, ['status' => 'open']);
        $closed = $this->repair($owner, [
            'status' => 'closed',
            'cost' => 200,
            'completed_at' => '2026-10-11 10:00:00',
        ]);
        $this->repair($this->user(), ['status' => 'open']);

        $this->actingAs($owner)
            ->get(route('repair-requests.index', ['status' => 'open']))
            ->assertOk()
            ->assertViewHas(
                'repairs',
                fn($repairs): bool =>
                $repairs->total() === 1 && $repairs->first()->is($open)
            );

        $this->actingAs($owner)
            ->get(route('repair-requests.index', ['status' => 'closed']))
            ->assertViewHas(
                'repairs',
                fn($repairs): bool =>
                $repairs->total() === 1 && $repairs->first()->is($closed)
            );
    }

    public function test_index_search_does_not_reveal_unrelated_comments(): void
    {
        $owner = $this->user();
        $visible = $this->repair($owner, ['subject' => 'Заміна кабелю']);
        $foreignOwner = $this->user();
        $foreign = $this->repair($foreignOwner, ['subject' => 'Інша несправність']);
        $this->comment($visible, $owner, 'Проблема сканера');
        $this->comment($foreign, $foreignOwner, 'Проблема сканера');

        $this->actingAs($owner)
            ->get(route('repair-requests.index', ['search' => 'сканера']))
            ->assertOk()
            ->assertViewHas(
                'repairs',
                fn($repairs): bool =>
                $repairs->total() === 1 && $repairs->first()->is($visible)
            );
    }

    public function test_index_paginates_accessible_repairs(): void
    {
        $owner = $this->user();
        RepairRequest::factory()
            ->count(25)
            ->for($owner, 'owner')
            ->for($this->department(), 'department')
            ->create();
        RepairRequest::factory()->count(3)->for($this->department(), 'department')->create();

        $this->actingAs($owner)
            ->get(route('repair-requests.index'))
            ->assertViewHas(
                'repairs',
                fn($repairs): bool =>
                $repairs->total() === 25 && $repairs->count() === 20
                    && $repairs->lastPage() === 2
            );

        $this->actingAs($owner)
            ->get(route('repair-requests.index', ['page' => 2]))
            ->assertViewHas(
                'repairs',
                fn($repairs): bool =>
                $repairs->total() === 25 && $repairs->count() === 5
            );
    }

    public function test_owner_can_create_repair_with_initial_comment_and_correct_foreign_keys(): void
    {
        $owner = $this->user();

        $this->actingAs($owner)
            ->post(route('repair-requests.store'), $this->payload([
                'subject' => 'Створена власником',
                'initial_comment' => 'Початковий опис',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $repair = RepairRequest::query()
            ->where('subject', 'Створена власником')
            ->firstOrFail();

        $this->assertSame($owner->id, $repair->owner_id);
        $this->assertDatabaseHas('repair_comments', [
            'repair_request_id' => $repair->id,
            'author_id' => $owner->id,
            'body' => 'Початковий опис',
        ]);
    }

    public function test_create_ignores_forged_owner_and_comment_author(): void
    {
        $owner = $this->user();
        $other = $this->user();

        $this->actingAs($owner)
            ->post(route('repair-requests.store'), $this->payload([
                'subject' => 'Створено без підміни',
                'initial_comment' => 'Автор має бути власником',
                'owner_id' => $other->id,
                'author_id' => $other->id,
                'review_file_path' => 'repair-reviews/other.pdf',
            ]))->assertSessionHasNoErrors();

        $repair = RepairRequest::query()
            ->where('subject', 'Створено без підміни')
            ->firstOrFail();

        $this->assertSame($owner->id, $repair->owner_id);
        $this->assertNull($repair->review_file_path);
        $this->assertDatabaseHas('repair_comments', [
            'repair_request_id' => $repair->id,
            'author_id' => $owner->id,
        ]);
    }

    public function test_create_without_initial_comment_creates_only_repair(): void
    {
        $owner = $this->user();

        $this->actingAs($owner)
            ->post(route('repair-requests.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('repair_requests', 1);
        $this->assertDatabaseCount('repair_comments', 0);
    }

    public function test_normal_create_cannot_set_closed_status(): void
    {
        $owner = $this->user();

        $this->actingAs($owner)
            ->post(route('repair-requests.store'), $this->payload([
                'status' => 'closed',
                'cost' => 150,
                'completed_at' => '2026-10-11T10:00',
            ]))->assertSessionHasErrors('status');

        $this->assertDatabaseCount('repair_requests', 0);
    }

    public function test_invalid_create_does_not_write_to_database(): void
    {
        $owner = $this->user();

        $this->actingAs($owner)
            ->post(route('repair-requests.store'), $this->payload([
                'subject' => '',
                'priority' => 99,
            ]))->assertSessionHasErrors(['subject', 'priority']);

        $this->assertDatabaseCount('repair_requests', 0);
    }

    public function test_owner_can_update_open_repair(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);

        $this->actingAs($owner)
            ->put(route('repair-requests.update', $repair), $this->payload([
                'subject' => 'Оновлено власником',
                'owner_id' => $this->user()->id,
            ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('repair_requests', [
            'id' => $repair->id,
            'owner_id' => $owner->id,
            'subject' => 'Оновлено власником',
        ]);
    }

    public function test_editor_can_update_assigned_open_repair(): void
    {
        $repair = $this->repair($this->user());
        $editor = $this->user();
        $this->participant($repair, $editor, 'editor');

        $this->actingAs($editor)
            ->put(route('repair-requests.update', $repair), $this->payload([
                'subject' => 'Оновлено редактором',
            ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('repair_requests', [
            'id' => $repair->id,
            'subject' => 'Оновлено редактором',
        ]);
    }

    public function test_viewer_cannot_update_repair(): void
    {
        $repair = $this->repair($this->user(), ['subject' => 'Без змін']);
        $viewer = $this->user();
        $this->participant($repair, $viewer, 'viewer');

        $this->actingAs($viewer)
            ->put(route('repair-requests.update', $repair), $this->payload([
                'subject' => 'Заборонена зміна',
            ]))->assertForbidden();

        $this->assertDatabaseHas('repair_requests', [
            'id' => $repair->id,
            'subject' => 'Без змін',
        ]);
    }

    public function test_unrelated_user_cannot_update_repair(): void
    {
        $repair = $this->repair($this->user(), ['subject' => 'Без змін']);

        $this->actingAs($this->user())
            ->put(route('repair-requests.update', $repair), $this->payload([
                'subject' => 'Заборонена зміна',
            ]))->assertForbidden();

        $this->assertSame('Без змін', $repair->fresh()->subject);
    }

    public function test_owner_cannot_update_closed_repair(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner, [
            'status' => 'closed',
            'cost' => 100,
            'completed_at' => '2026-10-11 10:00:00',
            'subject' => 'Закрита заявка',
        ]);

        $this->actingAs($owner)
            ->put(route('repair-requests.update', $repair), $this->payload([
                'subject' => 'Не має змінитися',
            ]))->assertForbidden();

        $this->assertSame('Закрита заявка', $repair->fresh()->subject);
    }

    public function test_owner_can_delete_repair_and_its_comments(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $comment = $this->comment($repair, $owner);

        $this->actingAs($owner)
            ->delete(route('repair-requests.destroy', $repair))
            ->assertRedirect(route('repair-requests.index'));

        $this->assertDatabaseMissing('repair_requests', ['id' => $repair->id]);
        $this->assertDatabaseMissing('repair_comments', ['id' => $comment->id]);
    }

    public function test_editor_cannot_delete_repair(): void
    {
        $repair = $this->repair($this->user());
        $editor = $this->user();
        $this->participant($repair, $editor, 'editor');

        $this->actingAs($editor)
            ->delete(route('repair-requests.destroy', $repair))
            ->assertForbidden();

        $this->assertDatabaseHas('repair_requests', ['id' => $repair->id]);
    }

    public function test_viewer_cannot_delete_repair(): void
    {
        $repair = $this->repair($this->user());
        $viewer = $this->user();
        $this->participant($repair, $viewer, 'viewer');

        $this->actingAs($viewer)
            ->delete(route('repair-requests.destroy', $repair))
            ->assertForbidden();

        $this->assertDatabaseHas('repair_requests', ['id' => $repair->id]);
    }

    public function test_regular_update_cannot_bypass_reviewer_closing(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);

        $this->actingAs($owner)
            ->put(route('repair-requests.update', $repair), $this->payload([
                'status' => 'closed',
                'cost' => 100,
                'completed_at' => '2026-10-11T10:00',
            ]))->assertSessionHasErrors('status');

        $this->assertSame('open', $repair->fresh()->status);
    }

    public function test_owner_can_create_comment_with_correct_author(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $other = $this->user();

        $this->actingAs($owner)
            ->post(route('repair-requests.comments.store', $repair), [
                'body' => 'Новий коментар',
                'author_id' => $other->id,
                'repair_request_id' => 999999,
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('repair_comments', [
            'repair_request_id' => $repair->id,
            'author_id' => $owner->id,
            'body' => 'Новий коментар',
        ]);
    }

    public function test_editor_can_create_comment_for_assigned_repair(): void
    {
        $repair = $this->repair($this->user());
        $editor = $this->user();
        $this->participant($repair, $editor, 'editor');

        $this->actingAs($editor)
            ->post(route('repair-requests.comments.store', $repair), [
                'body' => 'Від редактора',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('repair_comments', [
            'repair_request_id' => $repair->id,
            'author_id' => $editor->id,
            'body' => 'Від редактора',
        ]);
    }

    public function test_viewer_cannot_create_comment(): void
    {
        $repair = $this->repair($this->user());
        $viewer = $this->user();
        $this->participant($repair, $viewer, 'viewer');

        $this->actingAs($viewer)
            ->post(route('repair-requests.comments.store', $repair), [
                'body' => 'Заборонений коментар',
            ])->assertForbidden();

        $this->assertDatabaseCount('repair_comments', 0);
    }

    public function test_reviewer_cannot_create_comment_without_editor_role(): void
    {
        $repair = $this->repair($this->user());
        $reviewer = $this->user();
        $this->participant($repair, $reviewer, 'reviewer');

        $this->actingAs($reviewer)
            ->post(route('repair-requests.comments.store', $repair), [
                'body' => 'Заборонений коментар',
            ])->assertForbidden();

        $this->assertDatabaseCount('repair_comments', 0);
    }

    public function test_comment_author_editor_can_update_own_comment(): void
    {
        $repair = $this->repair($this->user());
        $editor = $this->user();
        $this->participant($repair, $editor, 'editor');
        $comment = $this->comment($repair, $editor, 'До редагування');

        $this->actingAs($editor)
            ->patch(route('repair-requests.comments.update', [$repair, $comment]), [
                'body' => 'Після редагування',
            ])->assertRedirect();

        $this->assertSame('Після редагування', $comment->fresh()->body);
    }

    public function test_owner_cannot_edit_other_authors_comment(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $editor = $this->user();
        $this->participant($repair, $editor, 'editor');
        $comment = $this->comment($repair, $editor, 'Чужий текст');

        $this->actingAs($owner)
            ->patch(route('repair-requests.comments.update', [$repair, $comment]), [
                'body' => 'Небажана зміна',
            ])->assertForbidden();

        $this->assertSame('Чужий текст', $comment->fresh()->body);
    }

    public function test_editor_cannot_delete_owner_comment(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $editor = $this->user();
        $this->participant($repair, $editor, 'editor');
        $comment = $this->comment($repair, $owner);

        $this->actingAs($editor)
            ->delete(route('repair-requests.comments.destroy', [$repair, $comment]))
            ->assertForbidden();

        $this->assertDatabaseHas('repair_comments', ['id' => $comment->id]);
    }

    public function test_comment_author_can_delete_own_comment(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $comment = $this->comment($repair, $owner);

        $this->actingAs($owner)
            ->delete(route('repair-requests.comments.destroy', [$repair, $comment]))
            ->assertRedirect();

        $this->assertDatabaseMissing('repair_comments', ['id' => $comment->id]);
    }

    public function test_closed_repair_blocks_comment_changes(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner, [
            'status' => 'closed',
            'cost' => 50,
            'completed_at' => '2026-10-11 10:00:00',
        ]);
        $comment = $this->comment($repair, $owner, 'Незмінний текст');

        $this->actingAs($owner)
            ->patch(route('repair-requests.comments.update', [$repair, $comment]), [
                'body' => 'Змінений текст',
            ])->assertForbidden();

        $this->actingAs($owner)
            ->post(route('repair-requests.comments.store', $repair), [
                'body' => 'Новий текст',
            ])->assertForbidden();

        $this->assertSame('Незмінний текст', $comment->fresh()->body);
        $this->assertDatabaseCount('repair_comments', 1);
    }

    public function test_nested_comment_cannot_be_updated_under_other_repair(): void
    {
        $owner = $this->user();
        $first = $this->repair($owner);
        $second = $this->repair($owner);
        $comment = $this->comment($second, $owner, 'Чужий дочірній запис');

        $this->actingAs($owner)
            ->patch(route('repair-requests.comments.update', [$first, $comment]), [
                'body' => 'Спроба підміни parent',
            ])->assertNotFound();

        $this->assertSame('Чужий дочірній запис', $comment->fresh()->body);
    }

    public function test_nested_comment_cannot_be_deleted_under_other_repair(): void
    {
        $owner = $this->user();
        $first = $this->repair($owner);
        $second = $this->repair($owner);
        $comment = $this->comment($second, $owner);

        $this->actingAs($owner)
            ->delete(route('repair-requests.comments.destroy', [$first, $comment]))
            ->assertNotFound();

        $this->assertDatabaseHas('repair_comments', ['id' => $comment->id]);
    }

    public function test_empty_comment_is_rejected_without_database_changes(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);

        $this->actingAs($owner)
            ->post(route('repair-requests.comments.store', $repair), [
                'body' => '',
            ])->assertSessionHasErrors('body');

        $this->assertDatabaseCount('repair_comments', 0);
    }

    public function test_comments_are_paginated_to_ten_per_page(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);

        for ($i = 0; $i < 12; $i++) {
            $this->comment($repair, $owner, 'Коментар ' . $i);
        }

        $this->actingAs($owner)
            ->get(route('repair-requests.show', $repair))
            ->assertOk()
            ->assertViewHas(
                'comments',
                fn($comments): bool =>
                $comments->total() === 12 && $comments->count() === 10
            );

        $this->actingAs($owner)
            ->get(route('repair-requests.show', [
                'repairRequest' => $repair->id,
                'comments_page' => 2,
            ]))
            ->assertViewHas(
                'comments',
                fn($comments): bool =>
                $comments->total() === 12 && $comments->count() === 2
            );
    }

    public function test_owner_can_assign_viewer_role(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $repair = $this->repair($owner);

        $this->actingAs($owner)
            ->post(route('repair-requests.participants.store', $repair), [
                'user_id' => $viewer->id,
                'role' => 'viewer',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('repair_request_user', [
            'repair_request_id' => $repair->id,
            'user_id' => $viewer->id,
            'role' => 'viewer',
        ]);
    }

    public function test_owner_can_change_existing_participant_role_without_duplicate(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $repair = $this->repair($owner);
        $this->participant($repair, $member, 'viewer');

        $this->actingAs($owner)
            ->post(route('repair-requests.participants.store', $repair), [
                'user_id' => $member->id,
                'role' => 'editor',
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('repair_request_user', [
            'repair_request_id' => $repair->id,
            'user_id' => $member->id,
            'role' => 'editor',
        ]);
        $this->assertDatabaseCount('repair_request_user', 1);
    }

    public function test_viewer_cannot_manage_participants(): void
    {
        $repair = $this->repair($this->user());
        $viewer = $this->user();
        $newMember = $this->user();
        $this->participant($repair, $viewer, 'viewer');

        $this->actingAs($viewer)
            ->post(route('repair-requests.participants.store', $repair), [
                'user_id' => $newMember->id,
                'role' => 'reviewer',
            ])->assertForbidden();

        $this->assertDatabaseMissing('repair_request_user', [
            'repair_request_id' => $repair->id,
            'user_id' => $newMember->id,
        ]);
    }

    public function test_editor_cannot_manage_participants(): void
    {
        $repair = $this->repair($this->user());
        $editor = $this->user();
        $member = $this->user();
        $this->participant($repair, $editor, 'editor');

        $this->actingAs($editor)
            ->post(route('repair-requests.participants.store', $repair), [
                'user_id' => $member->id,
                'role' => 'viewer',
            ])->assertForbidden();

        $this->assertDatabaseMissing('repair_request_user', [
            'repair_request_id' => $repair->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_of_other_repair_cannot_manage_participants(): void
    {
        $otherOwner = $this->user();
        $repair = $this->repair($this->user());
        $member = $this->user();

        $this->actingAs($otherOwner)
            ->post(route('repair-requests.participants.store', $repair), [
                'user_id' => $member->id,
                'role' => 'viewer',
            ])->assertForbidden();

        $this->assertDatabaseCount('repair_request_user', 0);
    }

    public function test_owner_can_remove_participant(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $repair = $this->repair($owner);
        $this->participant($repair, $member, 'viewer');

        $this->actingAs($owner)
            ->delete(route('repair-requests.participants.destroy', [$repair, $member]))
            ->assertRedirect(route('repair-requests.show', $repair));

        $this->assertDatabaseMissing('repair_request_user', [
            'repair_request_id' => $repair->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_non_owner_cannot_remove_participant(): void
    {
        $repair = $this->repair($this->user());
        $viewer = $this->user();
        $member = $this->user();
        $this->participant($repair, $viewer, 'viewer');
        $this->participant($repair, $member, 'editor');

        $this->actingAs($viewer)
            ->delete(route('repair-requests.participants.destroy', [$repair, $member]))
            ->assertForbidden();

        $this->assertDatabaseHas('repair_request_user', [
            'repair_request_id' => $repair->id,
            'user_id' => $member->id,
            'role' => 'editor',
        ]);
    }

    public function test_removing_nonparticipant_returns_not_found(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $other = $this->user();

        $this->actingAs($owner)
            ->delete(route('repair-requests.participants.destroy', [$repair, $other]))
            ->assertNotFound();
    }

    public function test_seventh_additional_participant_is_rejected(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);

        foreach (User::factory()->count(6)->create() as $user) {
            $this->participant($repair, $user, 'viewer');
        }

        $seventh = $this->user();

        $this->actingAs($owner)
            ->post(route('repair-requests.participants.store', $repair), [
                'user_id' => $seventh->id,
                'role' => 'editor',
            ])->assertSessionHasErrors('user_id');

        $this->assertSame(6, $repair->participatedUsers()->count());
        $this->assertDatabaseMissing('repair_request_user', [
            'repair_request_id' => $repair->id,
            'user_id' => $seventh->id,
        ]);
    }

    public function test_owner_reviewer_role_does_not_count_toward_six_additional_participants(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);

        foreach (User::factory()->count(6)->create() as $user) {
            $this->participant($repair, $user, 'viewer');
        }

        $this->actingAs($owner)
            ->post(route('repair-requests.participants.store', $repair), [
                'user_id' => (string) $owner->id,
                'role' => 'reviewer',
            ])->assertSessionHasNoErrors();

        $this->assertSame(7, $repair->participatedUsers()->count());
        $this->assertDatabaseHas('repair_request_user', [
            'repair_request_id' => $repair->id,
            'user_id' => $owner->id,
            'role' => 'reviewer',
        ]);
    }

    public function test_invalid_participant_role_is_rejected(): void
    {
        $owner = $this->user();
        $member = $this->user();
        $repair = $this->repair($owner);

        $this->actingAs($owner)
            ->post(route('repair-requests.participants.store', $repair), [
                'user_id' => $member->id,
                'role' => 'administrator',
            ])->assertSessionHasErrors('role');

        $this->assertDatabaseCount('repair_request_user', 0);
    }

    public function test_reviewer_can_close_ready_repair(): void
    {
        $repair = $this->repair($this->user(), [
            'status' => 'in_progress',
            'cost' => 500,
            'completed_at' => '2026-10-11 10:00:00',
        ]);
        $reviewer = $this->user();
        $this->participant($repair, $reviewer, 'reviewer');

        $this->actingAs($reviewer)
            ->patch(route('repair-requests.close', $repair))
            ->assertRedirect(route('repair-requests.show', $repair));

        $this->assertSame('closed', $repair->fresh()->status);
    }

    public function test_reviewer_cannot_close_without_cost(): void
    {
        $repair = $this->repair($this->user(), [
            'status' => 'in_progress',
            'cost' => null,
            'completed_at' => '2026-10-11 10:00:00',
        ]);
        $reviewer = $this->user();
        $this->participant($repair, $reviewer, 'reviewer');

        $this->actingAs($reviewer)
            ->patch(route('repair-requests.close', $repair))
            ->assertForbidden();

        $this->assertSame('in_progress', $repair->fresh()->status);
    }

    public function test_reviewer_cannot_close_without_completed_at(): void
    {
        $repair = $this->repair($this->user(), [
            'status' => 'in_progress',
            'cost' => 500,
            'completed_at' => null,
        ]);
        $reviewer = $this->user();
        $this->participant($repair, $reviewer, 'reviewer');

        $this->actingAs($reviewer)
            ->patch(route('repair-requests.close', $repair))
            ->assertForbidden();

        $this->assertSame('in_progress', $repair->fresh()->status);
    }

    public function test_owner_without_reviewer_role_cannot_close_repair(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner, [
            'status' => 'in_progress',
            'cost' => 500,
            'completed_at' => '2026-10-11 10:00:00',
        ]);

        $this->actingAs($owner)
            ->patch(route('repair-requests.close', $repair))
            ->assertForbidden();

        $this->assertSame('in_progress', $repair->fresh()->status);
    }

    public function test_editor_cannot_perform_reviewer_close(): void
    {
        $repair = $this->repair($this->user(), [
            'status' => 'in_progress',
            'cost' => 500,
            'completed_at' => '2026-10-11 10:00:00',
        ]);
        $editor = $this->user();
        $this->participant($repair, $editor, 'editor');

        $this->actingAs($editor)
            ->patch(route('repair-requests.close', $repair))
            ->assertForbidden();

        $this->assertSame('in_progress', $repair->fresh()->status);
    }

    public function test_reviewer_of_another_repair_cannot_close_current_repair(): void
    {
        $owner = $this->user();
        $first = $this->repair($owner, [
            'status' => 'in_progress',
            'cost' => 500,
            'completed_at' => '2026-10-11 10:00:00',
        ]);
        $second = $this->repair($owner, [
            'status' => 'in_progress',
            'cost' => 500,
            'completed_at' => '2026-10-11 10:00:00',
        ]);
        $reviewer = $this->user();
        $this->participant($first, $reviewer, 'reviewer');

        $this->actingAs($reviewer)
            ->patch(route('repair-requests.close', $second))
            ->assertForbidden();

        $this->assertSame('in_progress', $second->fresh()->status);
    }

    public function test_closed_repair_cannot_be_closed_again(): void
    {
        $repair = $this->repair($this->user(), [
            'status' => 'closed',
            'cost' => 500,
            'completed_at' => '2026-10-11 10:00:00',
        ]);
        $reviewer = $this->user();
        $this->participant($repair, $reviewer, 'reviewer');

        $this->actingAs($reviewer)
            ->patch(route('repair-requests.close', $repair))
            ->assertForbidden();

        $this->assertSame('closed', $repair->fresh()->status);
    }

    public function test_owner_explicitly_assigned_reviewer_can_close(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner, [
            'status' => 'in_progress',
            'cost' => 500,
            'completed_at' => '2026-10-11 10:00:00',
        ]);
        $this->participant($repair, $owner, 'reviewer');

        $this->actingAs($owner)
            ->patch(route('repair-requests.close', $repair))
            ->assertRedirect(route('repair-requests.show', $repair));

        $this->assertSame('closed', $repair->fresh()->status);
    }

    public function test_editor_can_upload_private_pdf_to_open_repair(): void
    {
        $repair = $this->repair($this->user());
        $editor = $this->user();
        $this->participant($repair, $editor, 'editor');

        $this->actingAs($editor)
            ->post(route('repair-requests.review-file.store', $repair), [
                'review_file' => UploadedFile::fake()->create('review.pdf', 20, 'application/pdf'),
            ])->assertSessionHasNoErrors();

        $this->assertNotNull($repair->fresh()->review_file_path);
    }

    public function test_viewer_cannot_upload_private_pdf(): void
    {
        $repair = $this->repair($this->user());
        $viewer = $this->user();
        $this->participant($repair, $viewer, 'viewer');

        $this->actingAs($viewer)
            ->post(route('repair-requests.review-file.store', $repair), [
                'review_file' => UploadedFile::fake()->create('review.pdf', 20, 'application/pdf'),
            ])->assertForbidden();

        $this->assertNull($repair->fresh()->review_file_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_closed_repair_blocks_private_pdf_replacement(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner, [
            'status' => 'closed',
            'cost' => 500,
            'completed_at' => '2026-10-11 10:00:00',
        ]);

        $this->actingAs($owner)
            ->post(route('repair-requests.review-file.store', $repair), [
                'review_file' => UploadedFile::fake()->create('review.pdf', 20, 'application/pdf'),
            ])->assertForbidden();

        $this->assertNull($repair->fresh()->review_file_path);
    }

    public function test_owner_can_download_private_review_pdf(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $this->storedReview($repair);

        $this->actingAs($owner)
            ->get(route('repair-requests.review-file.download', $repair))
            ->assertOk()
            ->assertStreamedContent('PRIVATE REVIEW CONTENT');
    }

    public function test_viewer_can_download_private_review_pdf(): void
    {
        $repair = $this->repair($this->user());
        $viewer = $this->user();
        $this->participant($repair, $viewer, 'viewer');
        $this->storedReview($repair);

        $this->actingAs($viewer)
            ->get(route('repair-requests.review-file.download', $repair))
            ->assertOk()
            ->assertStreamedContent('PRIVATE REVIEW CONTENT');
    }

    public function test_unrelated_user_cannot_download_private_pdf(): void
    {
        $repair = $this->repair($this->user());
        $this->storedReview($repair);

        $this->actingAs($this->user())
            ->get(route('repair-requests.review-file.download', $repair))
            ->assertForbidden();
    }

    public function test_guest_cannot_download_private_pdf(): void
    {
        $repair = $this->repair($this->user());
        $this->storedReview($repair);

        $this->get(route('repair-requests.review-file.download', $repair))
            ->assertRedirect(route('login'));
    }

    public function test_missing_private_pdf_returns_not_found(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $repair->review_file_path = 'repair-reviews/missing.pdf';
        $repair->saveOrFail();

        $this->actingAs($owner)
            ->get(route('repair-requests.review-file.download', $repair))
            ->assertNotFound();
    }

    public function test_query_string_cannot_override_private_pdf_path(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $this->storedReview($repair, 'repair-reviews/allowed.pdf', 'ALLOWED CONTENT');
        Storage::disk('local')->put('repair-reviews/secret.pdf', 'SECRET CONTENT');

        $url = route('repair-requests.review-file.download', $repair)
            . '?path=repair-reviews/secret.pdf';

        $this->actingAs($owner)
            ->get($url)
            ->assertOk()
            ->assertStreamedContent('ALLOWED CONTENT');
    }

    public function test_private_upload_rejects_wrong_file_type(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);

        $this->actingAs($owner)
            ->post(route('repair-requests.review-file.store', $repair), [
                'review_file' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])->assertSessionHasErrors('review_file');

        $this->assertNull($repair->fresh()->review_file_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_private_upload_rejects_file_over_2560_kb(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);

        $this->actingAs($owner)
            ->post(route('repair-requests.review-file.store', $repair), [
                'review_file' => UploadedFile::fake()->create('large.pdf', 2561, 'application/pdf'),
            ])->assertSessionHasErrors('review_file');

        $this->assertNull($repair->fresh()->review_file_path);
    }

    public function test_public_defect_download_requires_access_to_repair(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);
        $viewer = $this->user();
        $stranger = $this->user();
        $this->participant($repair, $viewer, 'viewer');
        Storage::disk('public')->put('repair-defects/demo.pdf', 'DEFECT CONTENT');
        $repair->defect_path = 'repair-defects/demo.pdf';
        $repair->saveOrFail();

        $this->actingAs($viewer)
            ->get(route('repair-requests.download', $repair))
            ->assertOk();

        $this->actingAs($stranger)
            ->get(route('repair-requests.download', $repair))
            ->assertForbidden();
    }

    public function test_index_uses_eager_loading_instead_of_n_plus_one(): void
    {
        $owner = $this->user();
        $department = $this->department();

        RepairRequest::factory()
            ->count(15)
            ->for($owner, 'owner')
            ->for($department, 'department')
            ->create();

        $lazySelects = $this->selectQueries(function () use ($owner): void {
            $repairs = RepairRequest::query()
                ->where('owner_id', $owner->id)
                ->get();

            foreach ($repairs as $repair) {
                $repair->department->name;
                $repair->owner->name;
            }
        });

        $eagerSelects = $this->selectQueries(function () use ($owner): void {
            $this->actingAs($owner)
                ->get(route('repair-requests.index'))
                ->assertOk();
        });

        $this->assertGreaterThan($eagerSelects, $lazySelects);
    }

    public function test_comment_page_does_not_query_each_author_separately(): void
    {
        $owner = $this->user();
        $repair = $this->repair($owner);

        foreach (range(1, 10) as $number) {
            $this->comment($repair, $owner, 'Коментар ' . $number);
        }

        $selects = $this->selectQueries(function () use ($owner, $repair): void {
            $this->actingAs($owner)
                ->get(route('repair-requests.show', $repair))
                ->assertOk();
        });

        $this->assertLessThanOrEqual(12, $selects);
    }
}
