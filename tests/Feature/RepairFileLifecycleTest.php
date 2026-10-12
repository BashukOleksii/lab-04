<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\RepairRequest;
use App\Services\RepairRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class RepairFileLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function validPayload(Department $department): array
    {
        return [
            'department_id' => $department->id,
            'subject' => 'Дефект монітора',
            'priority' => 2,
            'status' => 'open',
            'urgent' => 0,
            'reported_at' => '2026-10-10T09:00',
            'cost' => null,
            'completed_at' => null,
        ];
    }

    private function storedRepairWithFile(string $relativePath = 'repair-defects/old.jpg'): RepairRequest
    {
        $department = Department::factory()->create();
        Storage::disk('public')->put($relativePath, 'test-file-content');

        return RepairRequest::factory()->for($department, 'department')->create([
            'defect_path' => $relativePath,
        ]);
    }

    public function test_optional_file_create_saves_null_path(): void
    {
        $department = Department::factory()->create();
        $this->post(route('repair-requests.store'), $this->validPayload($department))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull(RepairRequest::firstOrFail()->defect_path);
        $this->assertSame([], Storage::disk('public')->allFiles('repair-defects'));
    }

  

    public function test_invalid_upload_type_does_not_create_file_or_repair(): void
    {
        $department = Department::factory()->create();
        $data = $this->validPayload($department);
        $data['defect_file'] = UploadedFile::fake()->create('payload.txt', 5, 'text/plain');

        $this->post(route('repair-requests.store'), $data)
            ->assertSessionHasErrors('defect_file');

        $this->assertDatabaseCount('repair_requests', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('repair-defects'));
    }

    // T-19. Для N=3 maxFileKb=2560; файл на 2561 KB заборонений.
    public function test_file_larger_than_2560_kilobytes_is_rejected(): void
    {
        $department = Department::factory()->create();
        $data = $this->validPayload($department);
        $data['defect_file'] = UploadedFile::fake()->create('oversized.pdf', 2561, 'application/pdf');

        $this->post(route('repair-requests.store'), $data)
            ->assertSessionHasErrors('defect_file');

        $this->assertDatabaseCount('repair_requests', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('repair-defects'));
    }

    public function test_show_displays_image_preview_url(): void
    {
        $repair = $this->storedRepairWithFile('repair-defects/photo.jpg');
        $this->get(route('repair-requests.show', $repair))
            ->assertOk()
            ->assertSee('Зображення дефекту')
            ->assertSee('/storage/repair-defects/photo.jpg');
    }

    public function test_pdf_show_has_iframe_and_download_returns_attachment(): void
    {
        $repair = $this->storedRepairWithFile('repair-defects/report.pdf');
        $this->get(route('repair-requests.show', $repair))
            ->assertOk()->assertSee('PDF-документ дефекту')
            ->assertSee('/storage/repair-defects/report.pdf');

        $this->get(route('repair-requests.download', $repair))
            ->assertOk()
            ->assertHeader('Content-Disposition');
    }


    public function test_destroy_without_file_succeeds(): void
    {
        $repair = RepairRequest::factory()->create(['defect_path' => null]);
        $this->delete(route('repair-requests.destroy', $repair))
            ->assertRedirect(route('repair-requests.index'));
        $this->assertDatabaseMissing('repair_requests', ['id' => $repair->id]);
    }

    public function test_missing_physical_file_is_handled_in_show_download_and_destroy(): void
    {
        $repair = RepairRequest::factory()->create([
            'defect_path' => 'repair-defects/missing.pdf',
        ]);
        $this->get(route('repair-requests.show', $repair))
            ->assertOk()->assertSee('фізично відсутній');
        $this->get(route('repair-requests.download', $repair))
            ->assertNotFound();
        $this->delete(route('repair-requests.destroy', $repair))
            ->assertRedirect(route('repair-requests.index'));
        $this->assertDatabaseMissing('repair_requests', ['id' => $repair->id]);
    }

    public function test_failed_database_insert_does_not_leave_orphan_uploaded_file(): void
    {
        $department = Department::factory()->create();
        $payload = $this->validPayload($department);
        $payload['department_id'] = 999999; 

        try {
            app(RepairRequestService::class)->create(
                $payload,
                UploadedFile::fake()->image('failure.jpg')
            );
            $this->fail('Очікувалася FK-помилка збереження');
        } catch (QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }

        $this->assertDatabaseCount('repair_requests', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('repair-defects'));
    }

   
}
