<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('position', ['home_slider', 'home_banner', 'category_banner', 'popup'])->default('home_slider');
            $table->string('link_url')->nullable();
            $table->enum('link_type', ['external', 'product', 'category', 'offer'])->nullable();
            $table->unsignedBigInteger('link_id')->nullable();
            $table->unsignedBigInteger('company_user_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['position', 'is_active', 'start_date', 'end_date'], 'idx_ads_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
