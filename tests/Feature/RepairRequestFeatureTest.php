<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\RepairRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class RepairRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(Department $department): array
    {
        return [
            'department_id' => $department->id,
            'subject' => 'Несправний комп’ютер',
            'priority' => 2,
            'status' => 'open',
            'urgent' => 0,
            'reported_at' => '2026-10-10T10:00',
            'cost' => null,
            'completed_at' => null,
        ];
    }

    public function test_index_paginates_twenty_repairs_and_shows_only_current_page(): void
    {
        $department = Department::factory()->create();
        for ($i = 1; $i <= 41; $i++) {
            RepairRequest::factory()->for($department, 'department')->create([
                'subject' => sprintf('Ремонт-%02d', $i),
                'reported_at' => '2026-10-10 10:00:00',
            ]);
        }

        $this->get(route('repair-requests.index'))
            ->assertOk()
            ->assertViewHas('repairs', fn ($items) => $items instanceof LengthAwarePaginator
                && $items->perPage() === 20 && $items->total() === 41
                && $items->count() === 20 && $items->lastPage() === 3)
            ->assertSee('Ремонт-41')
            ->assertDontSee('Ремонт-01');

        $this->get(route('repair-requests.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('repairs', fn ($items) => $items->count() === 20
                && $items->currentPage() === 2)
            ->assertSee('Ремонт-21')
            ->assertDontSee('Ремонт-41');

        $this->get(route('repair-requests.index', ['page' => 3]))
            ->assertOk()
            ->assertViewHas('repairs', fn ($items) => $items->count() === 1
                && $items->currentPage() === 3)
            ->assertSee('Ремонт-01');
    }

    // T-04. Перший фільтр — department_id.
    public function test_index_filters_repairs_by_department(): void
    {
        $first = Department::factory()->create();
        $other = Department::factory()->create();
        RepairRequest::factory()->count(2)->for($first, 'department')->create();
        RepairRequest::factory()->count(3)->for($other, 'department')->create();

        $this->get(route('repair-requests.index', ['department_id' => $first->id]))
            ->assertOk()
            ->assertViewHas('repairs', fn ($items) => $items->total() === 2
                && $items->getCollection()->every(fn ($repair) => $repair->department_id === $first->id));
    }

    public function test_index_filters_each_repair_status(): void
    {
        $department = Department::factory()->create();
        RepairRequest::factory()->for($department, 'department')->open()->create();
        RepairRequest::factory()->for($department, 'department')->inProgress()->create();
        RepairRequest::factory()->for($department, 'department')->closed()->create();

        foreach (['open', 'in_progress', 'closed'] as $status) {
            $this->get(route('repair-requests.index', ['status' => $status]))
                ->assertOk()
                ->assertViewHas('repairs', fn ($items) => $items->total() === 1
                    && $items->first()->status === $status);
        }
    }

    public function test_pagination_preserves_filter_search_and_sort_query_parameters(): void
    {
        $department = Department::factory()->create();
        RepairRequest::factory()->count(25)->for($department, 'department')->open()
            ->create(['subject' => 'Принтер для тесту']);
        $params = [
            'department_id' => $department->id,
            'status' => 'open',
            'search' => 'Принтер',
            'sort' => 'priority',
            'direction' => 'asc',
        ];
        $response = $this->get(route('repair-requests.index', $params));
        $response->assertOk()->assertViewHas('repairs', fn ($items) => $items->lastPage() === 2);

        $repairs = $response->viewData('repairs');
        $url = $repairs->url(2);
        foreach ($params as $name => $value) {
            $this->assertStringContainsString(rawurlencode($name).'='.rawurlencode((string) $value), $url);
        }

        $this->get($url)->assertOk()
            ->assertViewHas('repairs', fn ($items) => $items->currentPage() === 2 && $items->total() === 25);
    }

    public function test_show_includes_department_and_comments(): void
    {
        $department = Department::factory()->create(['name' => 'Діагностичний відділ']);
        $repair = RepairRequest::factory()->for($department, 'department')->create(['subject' => 'Ремонт екрану']);
        $repair->repairComments()->create(['body' => 'Перевірено підсвітку екрана']);

        $this->get(route('repair-requests.show', $repair))
            ->assertOk()
            ->assertSee('Ремонт екрану')
            ->assertSee('Діагностичний відділ')
            ->assertSee('Перевірено підсвітку екрана');
    }

    public function test_valid_store_creates_repair_and_initial_comment(): void
    {
        $department = Department::factory()->create();
        $payload = $this->validPayload($department);
        $payload['initial_comment'] = 'Первинна діагностика';

        $this->post(route('repair-requests.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseCount('repair_requests', 1);
        $this->assertDatabaseHas('repair_requests', [
            'department_id' => $department->id,
            'subject' => 'Несправний комп’ютер',
            'status' => 'open',
        ]);
        $repair = RepairRequest::firstOrFail();
        $this->assertDatabaseHas('repair_comments', [
            'repair_request_id' => $repair->id,
            'body' => 'Первинна діагностика',
        ]);
    }

    public function test_invalid_store_returns_field_errors_and_preserves_old_input(): void
    {
        $department = Department::factory()->create();
        $payload = $this->validPayload($department);
        $payload['subject'] = '';
        $payload['priority'] = 7;
        $payload['status'] = 'closed';

        $this->from(route('repair-requests.create'))
            ->post(route('repair-requests.store'), $payload)
            ->assertRedirect(route('repair-requests.create'))
            ->assertSessionHasErrors(['subject', 'priority', 'cost', 'completed_at'])
            ->assertSessionHas('_old_input.priority', 7);

        $this->assertDatabaseCount('repair_requests', 0);
    }

    public function test_valid_update_changes_data_without_creating_new_record(): void
    {
        $department = Department::factory()->create();
        $repair = RepairRequest::factory()->for($department, 'department')->open()->create();
        $payload = $this->validPayload($department);
        $payload['subject'] = 'Ремонт завершений';
        $payload['status'] = 'closed';
        $payload['cost'] = 0;
        $payload['completed_at'] = '2026-10-11T10:00';

        $this->put(route('repair-requests.update', $repair), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('repair-requests.show', $repair));

        $this->assertDatabaseCount('repair_requests', 1);
        $this->assertDatabaseHas('repair_requests', [
            'id' => $repair->id, 'subject' => 'Ремонт завершений',
            'status' => 'closed', 'cost' => 0,
        ]);
    }

    public function test_invalid_update_leaves_original_repair_unchanged(): void
    {
        $department = Department::factory()->create();
        $repair = RepairRequest::factory()->for($department, 'department')->create(['subject' => 'До ремонту']);
        $payload = $this->validPayload($department);
        $payload['subject'] = 'Не слід зберігати';
        $payload['priority'] = 99;

        $this->put(route('repair-requests.update', $repair), $payload)
            ->assertSessionHasErrors('priority');

        $this->assertDatabaseHas('repair_requests', ['id' => $repair->id, 'subject' => 'До ремонту']);
        $this->assertDatabaseCount('repair_requests', 1);
    }

    public function test_missing_repair_id_returns_404(): void
    {
        $this->get(route('repair-requests.show', 999999))->assertNotFound();
        $this->get(route('repair-requests.edit', 999999))->assertNotFound();
    }

    public function test_regression_index_is_accessible_without_login_or_policy(): void
    {
        $this->get(route('repair-requests.index'))
            ->assertOk()
            ->assertSee('Заявки на ремонт');
    }

    public function test_search_finds_both_subject_and_child_comment_text(): void
    {
        $department = Department::factory()->create();
        $bySubject = RepairRequest::factory()->for($department, 'department')
            ->create(['subject' => 'Проблема сканера']);
        $byComment = RepairRequest::factory()->for($department, 'department')
            ->create(['subject' => 'Заміна кабелю']);
        $byComment->repairComments()->create(['body' => 'Сканера не бачить система']);
        RepairRequest::factory()->for($department, 'department')->create(['subject' => 'Інша заявка']);

        $this->get(route('repair-requests.index', ['search' => 'сканера']))
            ->assertOk()->assertViewHas('repairs', fn ($items) => $items->total() === 2
                && $items->getCollection()->pluck('id')->sort()->values()->all()
                    === collect([$bySubject->id, $byComment->id])->sort()->values()->all());
    }

    public function test_combined_filters_return_empty_state_for_no_matches(): void
    {
        $department = Department::factory()->create();
        RepairRequest::factory()->for($department, 'department')->open()->create();
        $this->get(route('repair-requests.index', [
            'department_id' => $department->id,
            'status' => 'closed',
        ]))->assertOk()->assertSee('Заявок не знайдено')
            ->assertViewHas('repairs', fn ($items) => $items->total() === 0);
    }

    public function test_priority_and_date_sort_orders_are_stable(): void
    {
        $department = Department::factory()->create();
        $base = ['reported_at' => '2026-10-10 10:00:00'];
        $low = RepairRequest::factory()->for($department, 'department')->create($base + ['priority' => 1]);
        $high = RepairRequest::factory()->for($department, 'department')->create($base + ['priority' => 3]);
        $highNewerId = RepairRequest::factory()->for($department, 'department')->create($base + ['priority' => 3]);

        $this->get(route('repair-requests.index', ['sort' => 'priority', 'direction' => 'desc']))
            ->assertViewHas('repairs', fn ($items) => $items->pluck('id')->all()
                === [$highNewerId->id, $high->id, $low->id]);
        $this->get(route('repair-requests.index', ['sort' => 'date', 'direction' => 'asc']))
            ->assertViewHas('repairs', fn ($items) => $items->pluck('id')->all()
                === [$low->id, $high->id, $highNewerId->id]);
    }

    public function test_create_and_edit_pages_render(): void
    {
        $department = Department::factory()->create();
        $repair = RepairRequest::factory()->for($department, 'department')->create();
        $this->get(route('repair-requests.create'))->assertOk()
            ->assertSee('Створення заявки');
        $this->get(route('repair-requests.edit', $repair))->assertOk()
            ->assertSee('Редагування заявки');
    }
}
