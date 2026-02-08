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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();

            // الشركة المالكة للعرض (users.id where user_type=company)
            $table->foreignId('company_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // public: للجميع - private: مخصص
            $table->enum('scope', ['public', 'private'])->default('public');

            // حالة العرض
            $table->enum('status', ['draft', 'active', 'expired', 'paused'])->default('draft');

            $table->string('title');
            $table->text('description')->nullable();

            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
