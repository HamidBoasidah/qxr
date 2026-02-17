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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_no')->unique();

            // الشركة المستقبِلة للطلب
            $table->foreignId('company_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            // العميل صاحب الطلب
            $table->foreignId('customer_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('status', [
                'pending',
                'approved',
                'preparing',
                'shipped',
                'delivered',
                'rejected',
                'cancelled'
            ])->default('pending');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            // من وافق من جهة الشركة
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('delivered_at')->nullable();

            $table->text('notes_customer')->nullable();
            $table->text('notes_company')->nullable();

            $table->timestamps();

            $table->index(['company_user_id', 'status']);
            $table->index(['customer_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
