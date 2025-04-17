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
        Schema::whenTableDoesntHaveColumn(
            'media',
            'scope_type',
            function (Blueprint $table) {
                $table->string('scope_type', 20)->nullable()->comment('范围类型');
            }
        );
        Schema::whenTableDoesntHaveColumn(
            'media',
            'scope_id',
            function (Blueprint $table) {
                $table->unsignedBigInteger('scope_id')->default(0)->comment('范围');
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::whenTableHasColumn(
            'media',
            'scope_type',
            function (Blueprint $table) {
                $table->dropColumn('scope_type');
            }
        );
        Schema::whenTableHasColumn(
            'media',
            'scope_id',
            function (Blueprint $table) {
                $table->dropColumn('scope_id');
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return config('mediable.connection_name', parent::getConnection());
    }
};
