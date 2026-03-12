<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sn_contents', function (Blueprint $table) {
            $table->comment('内容');
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('team_id')->nullable()->comment('团队ID');
            $table->morphs('contentable');
            $table->longtext('content')->nullable()->comment('内容');
            $table->timestamps();
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sn_contents');
    }
};
