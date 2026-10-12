<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\RepairComment;
use App\Models\RepairRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaEloquentTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_schema_contains_all_three_domain_tables_and_columns(): void
    {
        $this->assertTrue(Schema::hasTable('departments'));
        $this->assertTrue(Schema::hasTable('repair_requests'));
        $this->assertTrue(Schema::hasTable('repair_comments'));
        $this->assertTrue(Schema::hasColumns('departments', ['id', 'name', 'code']));
        $this->assertTrue(Schema::hasColumns('repair_requests', [
            'id', 'department_id', 'subject', 'priority', 'status',
            'reported_at', 'cost', 'urgent', 'completed_at', 'defect_path',
        ]));
        $this->assertTrue(Schema::hasColumns('repair_comments', [
            'id', 'repair_request_id', 'body',
        ]));
    }

    public function test_factories_create_correct_relationship_graph_without_manual_foreign_keys(): void
    {
        $repair = RepairRequest::factory()->closed()->create();
        $comment = RepairComment::factory()->for($repair, 'repairRequest')->create();

        $this->assertInstanceOf(Department::class, $repair->department);
        $this->assertTrue($repair->department->repairRequests->contains($repair));
        $this->assertTrue($repair->repairComments->contains($comment));
        $this->assertTrue($comment->repairRequest->is($repair));
        $this->assertSame('closed', $repair->status);
        $this->assertNotNull($repair->cost);
        $this->assertNotNull($repair->completed_at);
    }

    public function test_factory_states_and_model_casts_match_repair_domain(): void
    {
        $open = RepairRequest::factory()->open()->create(['urgent' => true]);
        $progress = RepairRequest::factory()->inProgress()->create();
        $closed = RepairRequest::factory()->closed()->create();

        $this->assertSame('open', $open->status);
        $this->assertTrue($open->urgent);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $open->reported_at);
        $this->assertSame('in_progress', $progress->status);
        $this->assertNull($progress->completed_at);
        $this->assertSame('closed', $closed->status);
        $this->assertNotNull($closed->completed_at);
    }

    public function test_delete_policy_cascades_comments_when_repair_is_deleted(): void
    {
        $repair = RepairRequest::factory()->create();
        $comment = $repair->repairComments()->create(['body' => 'Історія ремонту']);

        $repair->delete();

        $this->assertDatabaseMissing('repair_requests', ['id' => $repair->id]);
        $this->assertDatabaseMissing('repair_comments', ['id' => $comment->id]);
    }

    public function test_repair_index_does_not_generate_a_department_query_per_card(): void
    {
        $department = Department::factory()->create();
        RepairRequest::factory()->count(20)->for($department, 'department')->create();

        DB::flushQueryLog();
        DB::enableQueryLog();
        try {
            $this->get(route('repair-requests.index'))->assertOk();
            $queries = DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        $selects = array_filter($queries, static fn (array $query): bool =>
            str_starts_with(strtolower(ltrim($query['query'])), 'select')
        );
        $this->assertLessThanOrEqual(6, count($selects));
    }
}
