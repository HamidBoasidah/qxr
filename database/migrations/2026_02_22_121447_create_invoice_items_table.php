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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            // لقطة وصف المنتج وقت الفاتورة (اختياري)
            $table->string('description_snapshot')->nullable();

            $table->unsignedInteger('qty');

            // Snapshots
            $table->decimal('unit_price_snapshot', 12, 2);
            $table->decimal('line_total_snapshot', 12, 2);

            // حقول Snapshot للاسترجاع
            $table->date('expiry_date')->nullable();
            $table->enum('discount_type', ['percent', 'fixed'])->nullable();
            $table->decimal('discount_value', 15, 4)->nullable();
            $table->boolean('is_bonus')->default(false);

            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
