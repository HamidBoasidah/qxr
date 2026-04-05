<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_invoices', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('original_invoice_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('return_policy_id');
            $table->decimal('total_refund_amount', 15, 4);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // منع الاسترجاع المزدوج
            $table->unique('original_invoice_id', 'uq_original_invoice');

            $table->foreign('original_invoice_id')
                ->references('id')
                ->on('invoices')
                ->restrictOnDelete();

            $table->foreign('company_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('return_policy_id')
                ->references('id')
                ->on('return_policies')
                ->restrictOnDelete();

            $table->index(['company_id', 'created_at'], 'idx_company_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_invoices');
    }
};
