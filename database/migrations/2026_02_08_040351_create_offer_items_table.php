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
        Schema::create('offer_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('offer_id')
                ->constrained('offers')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // الحد الأدنى للكمية لتفعيل العرض
            $table->unsignedInteger('min_qty')->default(1);

            // نوع المكافأة: خصم % أو خصم مبلغ ثابت أو بونص كمية
            $table->enum('reward_type', ['discount_percent', 'discount_fixed', 'bonus_qty']);

            // حقول الخصم
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->decimal('discount_fixed', 12, 2)->nullable();

            // حقول البونص
            $table->foreignId('bonus_product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->unsignedInteger('bonus_qty')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['offer_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_items');
    }
};
