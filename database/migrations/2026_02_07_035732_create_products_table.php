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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // الشركة المالكة للمنتج (users.id حيث user_type = company)
            $table->foreignId('company_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // تصنيف المنتج
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            // اسم المنتج
            $table->string('name');

            // كود المنتج (اختياري)
            $table->string('sku')->nullable();

            // وصف المنتج
            $table->text('description')->nullable();

            // وحدة البيع (باكت / علبة / كرتون ...)
            $table->string('unit_name');

            // السعر الأساسي
            $table->decimal('base_price', 12, 2)->default(0);

            // حالة المنتج
            $table->boolean('is_active')->default(true);

            // الصورة الرئيسية للمنتج (مسار أو اسم الملف داخل التخزين)
            $table->string('main_image')->nullable();

            $table->timestamps();

            // فهارس للأداء
            $table->index('company_user_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
