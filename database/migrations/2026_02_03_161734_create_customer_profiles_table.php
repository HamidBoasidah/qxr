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
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();

            // الربط مع حساب المستخدم (العميل)
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->unique();

            // اسم المنشأة (صيدلية / مخزن / مركز ...)
            $table->string('business_name');

            // تصنيف العميل (صيدلية / مخزن ...)
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            // العنوان الرئيسي (اختياري)
            $table->foreignId('main_address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete();

            // حالة البروفايل
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
