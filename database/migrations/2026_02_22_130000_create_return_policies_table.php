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
        Schema::create('return_policies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->string('name');
            // عدد الايام المسموح بالاستراجاع بعد اصدار الفاتورة
            $table->unsignedInteger('return_window_days');
            // الحد الاقصى لنسبة الاسترجاع من الفاتورة الاصلية
            $table->decimal('max_return_ratio', 5, 4);
            // هل يتم ارجاع البونص
            $table->boolean('bonus_return_enabled')->default(false);
            // نسبة استرجاع البونص
            $table->decimal('bonus_return_ratio', 5, 4)->nullable();
            // يعني هل نسترجع مبلغ الخصم الي تم اعطاه العميل ام لا
            $table->boolean('discount_deduction_enabled')->default(true);
            $table->unsignedInteger('min_days_before_expiry')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index(['company_id', 'is_default'], 'idx_company_default');
            $table->index(['company_id', 'is_active'], 'idx_company_active');
        });

        // إضافة foreign key لـ return_policy_id في جدول invoices بعد إنشاء return_policies
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreign('return_policy_id')
                ->references('id')
                ->on('return_policies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['return_policy_id']);
        });

        Schema::dropIfExists('return_policies');
    }
};
