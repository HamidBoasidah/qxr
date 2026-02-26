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
        Schema::create('landing_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->onDelete('cascade');
            $table->enum('type', [
                'hero',
                'features',
                'services',
                'steps',
                'testimonials',
                'faq',
                'cta',
                'stats',
                'mobile_app'
            ]);
            $table->json('title')->nullable(); // {ar: '', en: ''}
            $table->json('subtitle')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // layout, colors, etc.
            $table->timestamps();

            $table->index(['landing_page_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_sections');
    }
};
