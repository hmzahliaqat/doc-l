<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update any NULL blade_content values with content from html_content
        DB::statement('UPDATE email_templates SET blade_content = html_content WHERE blade_content IS NULL AND html_content IS NOT NULL');

        // Then make the changes to the table structure
        Schema::table('email_templates', function (Blueprint $table) {
            $table->text('blade_content')->nullable(false)->change();
            $table->dropColumn('html_content');
            $table->dropColumn('text_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->text('html_content')->nullable();
            $table->text('text_content')->nullable();
            $table->text('blade_content')->nullable()->change();
        });
    }
};
