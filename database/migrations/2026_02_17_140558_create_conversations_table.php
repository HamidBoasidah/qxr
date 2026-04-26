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
        if (Schema::hasTable('conversations')) {
            // الجدول موجود مسبقاً، نضيف العمود الجديد فقط إذا ما كان موجود
            if (!Schema::hasColumn('conversations', 'order_id')) {
                Schema::table('conversations', function (Blueprint $table) {
                    $table->foreignId('order_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('orders')
                        ->nullOnDelete();
                    $table->unique('order_id');
                });
            }
            return;
        }

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->unique('order_id'); // محادثة واحدة لكل طلب
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
