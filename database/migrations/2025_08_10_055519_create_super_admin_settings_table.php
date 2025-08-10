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
        Schema::create('super_admin_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->nullable();
            $table->string('app_logo')->nullable();
            $table->string('video_url')->nullable();
            $table->string('stripe_app_key')->nullable();
            $table->string('stripe_secret_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_settings');
    }
};
