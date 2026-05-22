<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sn_sms_logs', function (Blueprint $table) {
            $table->comment('短信记录');
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('idd_code', 20)->comment('国际区号');
            $table->string('mobile', 20)->comment('手机号');
            $table->string('event', 20)->comment('事件');
            $table->string('code', 20)->comment('验证码');
            $table->unsignedTinyInteger('times')->default(0)->comment('发送次数');
            $table->ipAddress('ip_address')->nullable()->comment('IP地址');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sn_sms_logs');
    }
};
