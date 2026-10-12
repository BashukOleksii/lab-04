<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\RepairRequest;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rejects_duplicate_department_name_and_code(): void
    {
        Department::factory()->create(['name' => 'ІТ відділ', 'code' => 'IT']);

        $this->post(route('departments.store'), [
            'name' => 'ІТ відділ', 'code' => 'IT',
        ])->assertSessionHasErrors(['name', 'code']);

        $this->assertDatabaseCount('departments', 1);
    }

    public function test_database_unique_constraint_rejects_duplicate_code(): void
    {
        Department::factory()->create(['code' => 'IT']);

        $this->expectException(QueryException::class);
        Department::factory()->create(['code' => 'IT']);
    }

    public function test_department_with_repairs_cannot_be_deleted(): void
    {
        $department = Department::factory()->create();
        RepairRequest::factory()->for($department, 'department')->create();

        $this->delete(route('departments.destroy', $department))
            ->assertRedirect(route('departments.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->assertDatabaseCount('repair_requests', 1);
    }

    public function test_department_creation_and_show_page_work(): void
    {
        $this->post(route('departments.store'), [
            'name' => 'Тестова лабораторія', 'code' => 'LAB-9',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $department = Department::firstOrFail();
        $this->get(route('departments.show', $department))
            ->assertOk()->assertSee('Тестова лабораторія')->assertSee('LAB-9');
    }

    public function test_department_update_ignores_itself_but_rejects_other_name(): void
    {
        $first = Department::factory()->create(['name' => 'Первинний', 'code' => 'ONE']);
        Department::factory()->create(['name' => 'Інший', 'code' => 'TWO']);

        $this->put(route('departments.update', $first), [
            'name' => 'Первинний', 'code' => 'ONE',
        ])->assertSessionHasNoErrors()->assertRedirect(route('departments.show', $first));

        $this->put(route('departments.update', $first), [
            'name' => 'Інший', 'code' => 'ONE',
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseHas('departments', ['id' => $first->id, 'name' => 'Первинний']);
    }

    public function test_department_can_be_updated_and_deleted_without_repairs(): void
    {
        $department = Department::factory()->create(['name' => 'Старий відділ', 'code' => 'OLD']);

        $this->put(route('departments.update', $department), [
            'name' => 'Новий відділ', 'code' => 'NEW',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'code' => 'NEW']);

        $this->delete(route('departments.destroy', $department))
            ->assertRedirect(route('departments.index'))->assertSessionHas('success');
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_department_filter_and_ten_per_page_pagination_work(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Department::factory()->create([
                'name' => sprintf('Лабораторія %02d', $i),
                'code' => sprintf('L%02d', $i),
            ]);
        }
        Department::factory()->create(['name' => 'Бухгалтерія', 'code' => 'ACC']);

        $response = $this->get(route('departments.index', ['search' => 'Лабораторія']));
        $response->assertOk()->assertViewHas('departments', fn ($items) =>
            $items->total() === 12 && $items->count() === 10 && $items->lastPage() === 2
        );
        $this->assertStringContainsString('search=', $response->viewData('departments')->url(2));
        $this->get(route('departments.index', ['search' => 'Лабораторія', 'page' => 2]))
            ->assertOk()->assertViewHas('departments', fn ($items) => $items->count() === 2);
    }

    public function test_missing_department_returns_404(): void
    {
        $this->get(route('departments.show', 999999))->assertNotFound();
    }
}
