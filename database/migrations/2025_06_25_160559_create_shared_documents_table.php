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
        Schema::create('shared_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('shared_document_name')->nullable();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('access_hash', 64)->unique();
            $table->boolean('status')->default(true);
            $table->json('canvas')->nullable();
            $table->string('pages')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->integer('valid_for')->default(60);
            $table->string('file_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onDelete('cascade');

            $table->foreign('document_id')
            ->references('id')
            ->on('documents')
            ->onDelete('cascade');

            $table->foreign('employee_id')
            ->references('id')
            ->on('employees')
            ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shared_documents');
    }
};
