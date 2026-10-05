<?php

namespace Tests\Feature;

use App\Models\WorkLog;
use App\Services\WorkLogAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class WorkLogTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_submit_text_log_with_less_than_20_characters(): void
    {
        $response = $this->post('/logs/text', [
            'content' => 'Pendak',
        ]);

        $response->assertSessionHasErrors('content');
    }

    public function test_can_submit_text_log_successfully(): void
    {
        $this->mock(WorkLogAiService::class, function (MockInterface $mock) {
            $mock->shouldReceive('extractFromText')
                ->once()
                ->andReturn([
                    'success' => true,
                    'error' => null,
                    'data' => [
                        'title' => 'Fix Payment Gateway Error 500',
                        'project' => 'E-Commerce',
                        'module' => 'Checkout',
                        'type' => 'bug_fix',
                        'status' => 'completed',
                        'problem' => 'Tombol bayar error 500',
                        'before' => 'Error saat diskon 100%',
                        'actions' => ['Memperbaiki validasi voucher'],
                        'after' => 'Pembayaran lancar',
                        'impact' => 'User bisa checkout',
                        'testing' => ['Unit test voucher'],
                        'technologies' => ['PHP', 'Laravel'],
                        'next_step' => null,
                        'tags' => ['checkout', 'payment'],
                        'logged_at' => '2026-10-05',
                        'transcript' => 'Hari ini aku ngerjain fix payment gateway error 500 saat diskon 100%. Sebelumnya tombol bayar throw exception. Jadi aku perbaiki validasi voucher. Hasilnya checkout lancar.',
                    ],
                ]);
        });

        $input = 'Hari ini aku ngerjain fix payment gateway error 500 saat diskon 100%. Sebelumnya tombol bayar throw exception. Jadi aku perbaiki validasi voucher. Hasilnya checkout lancar.';

        $response = $this->post('/logs/text', [
            'content' => $input,
        ]);

        $this->assertDatabaseHas('work_logs', [
            'title' => 'Fix Payment Gateway Error 500',
            'project' => 'E-Commerce',
            'source' => 'text',
            'audio_path' => null,
        ]);

        $log = WorkLog::where('title', 'Fix Payment Gateway Error 500')->first();
        $response->assertRedirect(route('logs.show', $log->id_work_log));
    }
}
