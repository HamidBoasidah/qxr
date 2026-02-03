<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_tag', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('tag_id')
                ->constrained('tags')
                ->cascadeOnDelete();

            $table->timestamps();

            // يمنع تكرار نفس التاج لنفس الشركة
            $table->unique(['company_user_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_tag');
    }
};
