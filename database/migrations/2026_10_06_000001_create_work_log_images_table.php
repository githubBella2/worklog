<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_log_images', function (Blueprint $table) {
            $table->id('id_work_log_image');
            $table->foreignId('id_work_log')
                ->constrained('work_logs', 'id_work_log')
                ->cascadeOnDelete();
            $table->string('kind'); // before | after
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['id_work_log', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_log_images');
    }
};
