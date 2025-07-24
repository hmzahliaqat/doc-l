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
        Schema::table('shared_documents', function (Blueprint $table) {
            $table->string('shared_document_name')->nullable()->after('id');
            $table->integer('pages')->nullable()->after('access_hash');
            $table->json('canvas')->nullable()->after('employee_id');
            $table->boolean('is_signable')->default(false)->after('pdf_path');
            $table->boolean('can_add_picture')->default(false)->after('pdf_path');
            $table->boolean('can_add_text')->default(false)->after('pdf_path');
            $table->boolean('view_pages')->default(false)->after('pdf_path');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shared_documents', function (Blueprint $table) {
            //
        });
    }
};
