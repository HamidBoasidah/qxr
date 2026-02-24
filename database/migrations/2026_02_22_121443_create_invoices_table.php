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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_no')->unique();

            // فاتورة واحدة لكل طلب (1:1)
            $table->foreignId('order_id')
                ->unique()
                ->constrained('orders')
                ->cascadeOnDelete();

            // Snapshots
            $table->decimal('subtotal_snapshot', 12, 2)->default(0);
            $table->decimal('discount_total_snapshot', 12, 2)->default(0);
            $table->decimal('total_snapshot', 12, 2)->default(0);

            $table->timestamp('issued_at')->nullable();

            $table->enum('status', ['unpaid', 'paid', 'void'])->default('unpaid');

            // Optional note left by company or admin
            $table->text('note')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
