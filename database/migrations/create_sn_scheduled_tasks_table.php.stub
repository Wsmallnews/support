<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_scheduled_tasks', function (Blueprint $table) {
            $table->comment('定时调度任务');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');

            $table->morphs('schedulable');
            $table->string('action', 50)->comment('动作: publish/unpublish/price_change/...');
            $table->timestamp('scheduled_at')->comment('计划执行时间');
            $table->json('payload')->nullable()->comment('动作参数 JSON');

            $table->string('status', 20)->default('pending')->comment('任务状态: pending/executed/cancelled/failed');
            $table->timestamp('executed_at')->nullable()->comment('实际执行时间');
            $table->text('result')->nullable()->comment('执行结果/错误信息');

            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['schedulable_type', 'schedulable_id', 'status']);
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_scheduled_tasks');
    }
};
