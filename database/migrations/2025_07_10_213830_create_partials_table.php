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
        Schema::create('partials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('file_path');
            $table->string('file_type');
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
                ->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partials');
    }
};
