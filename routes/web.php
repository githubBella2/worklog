<?php

use App\Http\Controllers\WorkLogController;
use App\Http\Controllers\WorkLogImageController;
use App\Http\Controllers\WorkLogReportController;
use Illuminate\Support\Facades\Route;

// Timeline & Filter
Route::get('/', [WorkLogController::class, 'index'])->name('logs.index');

// Voice Recording Page
Route::get('/record', [WorkLogController::class, 'record'])->name('logs.record');

// Export 1 Week Logs to Markdown
Route::get('/export/markdown', [WorkLogController::class, 'exportMarkdown'])->name('logs.export');

// Work Log Management
Route::post('/logs/text', [WorkLogController::class, 'storeText'])->name('logs.storeText');
Route::post('/logs', [WorkLogController::class, 'store'])->name('logs.store');
Route::get('/logs/{workLog}', [WorkLogController::class, 'show'])->name('logs.show');
Route::get('/logs/{workLog}/edit', [WorkLogController::class, 'edit'])->name('logs.edit');
Route::put('/logs/{workLog}', [WorkLogController::class, 'update'])->name('logs.update');
Route::delete('/logs/{workLog}', [WorkLogController::class, 'destroy'])->name('logs.destroy');

// Before/After Screenshots
Route::get('/logs/{workLog}/download', [WorkLogImageController::class, 'download'])->name('logs.download');
Route::post('/logs/{workLog}/images', [WorkLogImageController::class, 'store'])->name('logs.images.store');
Route::put('/logs/{workLog}/images/{image}', [WorkLogImageController::class, 'update'])->name('logs.images.update');
Route::delete('/logs/{workLog}/images/{image}', [WorkLogImageController::class, 'destroy'])->name('logs.images.destroy');

// KPI Report (printable page + ZIP download)
Route::get('/report', [WorkLogReportController::class, 'index'])->name('logs.report');
Route::get('/report/zip', [WorkLogReportController::class, 'zip'])->name('logs.report.zip');
