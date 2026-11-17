<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('operations')) {
            Schema::create('operations', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->string('category', 20);
                $table->string('tool', 40);
                $table->string('file_name', 255)->nullable();
                $table->dateTime('created_at')->useCurrent();

                $table->index(['user_id', 'id'], 'idx_ops_user');
                $table->index('category', 'idx_ops_category');
                $table->foreign('user_id', 'fk_ops_user')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};

