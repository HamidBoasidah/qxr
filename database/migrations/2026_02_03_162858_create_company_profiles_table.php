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
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();

            // الربط مع حساب المستخدم (الشركة)
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->unique();

            // اسم الشركة التجاري
            $table->string('company_name');

            // تصنيف الشركة (مستلزمات/تجميل/معدات...)
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();

            // شعار الشركة (اختياري)
            $table->string('logo_path')->nullable();

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
        Schema::dropIfExists('company_profiles');
    }
};
