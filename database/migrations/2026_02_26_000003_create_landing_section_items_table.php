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
        Schema::create('landing_section_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_section_id')->constrained()->onDelete('cascade');
            $table->json('title')->nullable(); // {ar: '', en: ''}
            $table->json('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('icon')->nullable();
            $table->string('link')->nullable();
            $table->string('link_text')->nullable();
            $table->integer('order')->default(0);
            $table->json('data')->nullable(); // flexible data storage
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['landing_section_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_section_items');
    }
};
