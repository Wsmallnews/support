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
        Schema::create('sn_pages', function (Blueprint $table) {
            $table->comment('站点页面');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->string('scope_type', 60)->nullable()->comment('范围类型');
            $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');

            $table->string('title')->comment('标题');
            $table->string('slug')->comment('页面标识（scope 内唯一，路由段）');
            $table->unsignedBigInteger('composition_id')->nullable()->comment('绑定的内容编排');
            $table->string('status')->nullable()->comment('状态');
            $table->unsignedInteger('order_column')->nullable()->comment('排序');
            $table->json('options')->nullable()->comment('扩展选项');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['team_id', 'scope_type', 'scope_id', 'slug']);
            $table->index('composition_id');
            $table->index('order_column');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_pages');
    }
};
