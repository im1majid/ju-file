<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shares')) {
            Schema::create('shares', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable();
                $table->char('token', 16)->unique();
                $table->string('title', 150)->nullable();
                $table->string('original_name', 200);
                $table->char('stored_name', 32);
                $table->string('external_url', 500)->nullable();
                $table->string('mime', 120);
                $table->unsignedBigInteger('size');
                $table->enum('category', ['image', 'video', 'audio', 'document', 'other'])->default('other');
                $table->string('password_hash', 255)->nullable();
                $table->unsignedInteger('downloads')->default(0);
                $table->dateTime('expires_at')->nullable();
                $table->dateTime('created_at')->useCurrent();

                $table->index(['user_id', 'id'], 'idx_shares_user');
                $table->index('expires_at', 'idx_shares_expires');
                $table->foreign('user_id', 'fk_shares_user')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};

