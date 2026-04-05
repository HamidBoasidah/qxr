<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('return_invoice_id');
            $table->unsignedBigInteger('original_item_id');
            $table->unsignedInteger('returned_quantity');
            $table->decimal('unit_price_snapshot', 15, 4);
            $table->enum('discount_type_snapshot', ['percent', 'fixed'])->nullable();
            $table->decimal('discount_value_snapshot', 15, 4)->nullable();
            $table->date('expiry_date_snapshot')->nullable();
            $table->boolean('is_bonus')->default(false);
            $table->decimal('refund_amount', 15, 4);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('return_invoice_id')
                ->references('id')
                ->on('return_invoices')
                ->cascadeOnDelete();

            $table->foreign('original_item_id')
                ->references('id')
                ->on('invoice_items')
                ->restrictOnDelete();

            $table->index('return_invoice_id', 'idx_return_invoice');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_invoice_items');
    }
};
