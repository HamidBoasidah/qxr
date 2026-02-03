<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // استخدم أسماء الجداول من كونفيج Spatie (في حال عدّلتها)
        $rolesTable = config('permission.table_names.roles', 'roles');
        $permsTable = config('permission.table_names.permissions', 'permissions');

        Schema::table($rolesTable, function (Blueprint $table) {
            // اسم عرض مترجم للدور
            $table->json('display_name')->nullable()->after('name');
        });

        Schema::table($permsTable, function (Blueprint $table) {
            // اسم عرض مترجم للصلاحية
            $table->json('display_name')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $permsTable = config('permission.table_names.permissions', 'permissions');

        Schema::table($rolesTable, function (Blueprint $table) {
            $table->dropColumn('display_name');
        });

        Schema::table($permsTable, function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }
};
