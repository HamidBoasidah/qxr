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
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // مسار الصورة
            $table->string('path');

            // ترتيب عرض الصور (0 = أول صورة)
            $table->unsignedInteger('sort_order')->default(0);

            // حالة الصورة (اختياري)
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // فهارس مفيدة
            $table->index(['product_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
