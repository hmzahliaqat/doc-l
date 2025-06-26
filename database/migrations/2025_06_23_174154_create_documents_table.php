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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('pdf_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('file_path');
            $table->integer('pages');
            $table->json('canvas')->nullable();
            $table->boolean('is_trashed')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->string('update_date')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
