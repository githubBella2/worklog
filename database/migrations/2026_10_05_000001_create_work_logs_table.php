<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_logs', function (Blueprint $table) {
            $table->id('id_work_log');
            $table->string('title')->nullable();
            $table->string('project')->nullable();
            $table->string('module')->nullable();
            $table->string('type')->default('other'); // feature, bug_fix, improvement, refactor, other
            $table->string('status')->default('completed'); // completed, in_progress, blocked
            $table->text('problem')->nullable();
            $table->text('before')->nullable();
            $table->json('actions')->nullable();
            $table->text('after')->nullable();
            $table->text('impact')->nullable();
            $table->json('testing')->nullable();
            $table->json('technologies')->nullable();
            $table->text('next_step')->nullable();
            $table->json('tags')->nullable();
            $table->date('logged_at')->nullable();
            $table->text('transcript')->nullable();
            $table->string('audio_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_logs');
    }
};
