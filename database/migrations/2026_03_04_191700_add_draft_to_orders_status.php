<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'draft' to orders.status enum
        DB::statement("ALTER TABLE `orders` CHANGE `status` `status` ENUM('draft','pending','approved','preparing','shipped','delivered','rejected','cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous enum without 'draft'
        DB::statement("ALTER TABLE `orders` CHANGE `status` `status` ENUM('pending','approved','preparing','shipped','delivered','rejected','cancelled') NOT NULL DEFAULT 'pending'");
    }
};
