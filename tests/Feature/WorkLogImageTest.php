<?php

namespace Tests\Feature;

use App\Models\WorkLog;
use App\Models\WorkLogImage;
use App\Services\WorkLogAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class WorkLogImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_text_log_can_be_created_with_multiple_before_and_after_images(): void
    {
        $this->mock(WorkLogAiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractFromText')->once()->andReturn([
                'success' => true,
                'error' => null,
                'data' => ['title' => 'Fix halaman penilaian', 'type' => 'bug_fix', 'status' => 'completed', 'transcript' => 'x'],
            ]);
        });

        $this->post('/logs/text', [
            'content' => 'Hari ini aku benerin halaman penilaian RPL, sebelumnya nilai masih bisa diubah.',
            'before_images' => [UploadedFile::fake()->image('b1.png'), UploadedFile::fake()->image('b2.jpg')],
            'after_images' => [UploadedFile::fake()->image('a1.png')],
        ])->assertRedirect();

        $log = WorkLog::firstOrFail();
        $this->assertCount(2, $log->beforeImages);
        $this->assertCount(1, $log->afterImages);

        foreach ($log->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_non_image_files_are_rejected(): void
    {
        $this->post('/logs/text', [
            'content' => 'Hari ini aku benerin halaman penilaian RPL, sebelumnya nilai masih bisa diubah.',
            'before_images' => [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')],
        ])->assertSessionHasErrors('before_images.0');

        $this->assertDatabaseCount('work_logs', 0);
    }

    public function test_image_can_be_added_captioned_and_deleted_from_edit_page(): void
    {
        $log = WorkLog::create(['title' => 'Log tes', 'type' => 'feature', 'status' => 'completed']);

        $this->post(route('logs.images.store', $log), [
            'after_images' => [UploadedFile::fake()->image('after.png')],
        ])->assertRedirect(route('logs.edit', $log));

        $image = $log->images()->firstOrFail();
        Storage::disk('public')->assertExists($image->path);

        $this->put(route('logs.images.update', [$log, $image]), ['caption' => 'Tombol generate NIM'])
            ->assertRedirect();
        $this->assertSame('Tombol generate NIM', $image->fresh()->caption);

        $this->delete(route('logs.images.destroy', [$log, $image]))->assertRedirect();
        $this->assertDatabaseMissing('work_log_images', ['id_work_log_image' => $image->id_work_log_image]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_deleting_a_log_removes_its_image_files(): void
    {
        $log = WorkLog::create(['title' => 'Log tes', 'type' => 'feature', 'status' => 'completed']);
        $this->post(route('logs.images.store', $log), ['before_images' => [UploadedFile::fake()->image('b.png')]]);
        $path = $log->images()->firstOrFail()->path;

        $this->delete(route('logs.destroy', $log))->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        $this->assertSame(0, WorkLogImage::count());
    }

    public function test_report_page_and_zip_download_work(): void
    {
        $log = WorkLog::create(['title' => 'Log KPI', 'type' => 'feature', 'status' => 'completed', 'logged_at' => now()->toDateString()]);
        $this->post(route('logs.images.store', $log), [
            'before_images' => [UploadedFile::fake()->image('b.png')],
            'after_images' => [UploadedFile::fake()->image('a.png')],
        ]);

        $this->get(route('logs.report'))->assertOk()->assertSee('Log KPI');
        $this->get(route('logs.download', $log))->assertOk()->assertHeader('content-disposition');
        $this->get(route('logs.report.zip'))->assertOk();
    }
}
