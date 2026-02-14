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
        Schema::create('offer_targets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('offer_id')
                ->constrained('offers')
                ->cascadeOnDelete();

            // customer: عميل محدد - customer_category: تصنيف عميل - customer_tag: تاج عميل
            $table->enum('target_type', ['customer', 'customer_category', 'customer_tag']);

            // user_id أو category_id أو tag_id حسب target_type
            $table->unsignedBigInteger('target_id');

            $table->timestamps();
            
            $table->unique(['offer_id', 'target_type', 'target_id']);
            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_targets');
    }
};
