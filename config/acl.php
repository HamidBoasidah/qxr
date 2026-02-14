<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ACL Master Config (Fixed Permissions / Dynamic Roles)
    |--------------------------------------------------------------------------
    |
    | - هذا الملف هو "مصدر الحقيقة" لتعريف الموارد (resources) وأفعالها (actions)
    |   وتسميات واجهتها (labels) بلغات متعددة.
    | - تُستخدم هذه البيانات في:
    |   1) Seeder: لمزامنة أذونات Spatie (permissions) إلى قاعدة البيانات.
    |   2) الواجهة (Vue/Inertia): لبناء جدول الاختيار وعناوين الموارد/الأفعال.
    | - الأدوار Roles ديناميكية في DB مع display_name مترجم (spatie/laravel-translatable).
    |
    */

    // اللغات المدعومة (اختياري للتوحيد في الواجهة)
    'locales' => ['en', 'ar'],

    // الحارس الافتراضي
    'guard' => 'admin',

    // أفعال افتراضية يمكن استخدامها مستقبلًا لتقليل التكرار (غير مستخدمة الآن)
    'default_actions' => ['view', 'create', 'update', 'delete'],

    /*
    |--------------------------------------------------------------------------
    | Resources & Actions (ثابتة عبر الكود)
    |--------------------------------------------------------------------------
    | المفتاح = اسم المورد (kebab-case مفضل)
    | القيمة = مصفوفة الأفعال المسموحة لهذا المورد
    */
    'resources' => [
    'dashboard'    => ['view'],
    'areas'        => ['view', 'create', 'update', 'delete'],
    'districts'    => ['view', 'create', 'update', 'delete'],
    'governorates' => ['view', 'create', 'update', 'delete'],
    'categories'   => ['view', 'create', 'update', 'delete'],
    'addresses'    => ['view', 'create', 'update', 'delete'],
    'tags'         => ['view', 'create', 'update', 'delete'],
    'products'     => ['view', 'create', 'update', 'delete', 'activate', 'deactivate'],
    'offers'       => ['view', 'create', 'update', 'delete', 'activate', 'deactivate'],
    // backend management resources
    'users'        => ['view', 'create', 'update', 'delete'],
    'admins'       => ['view', 'create', 'update', 'delete'],
    'roles'        => ['view', 'create', 'update', 'delete'],
    'permissions'  => ['view'],
    'profile'      => ['view'],
    'activitylogs'  => ['view'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Labels (واجهة المستخدم)
    |--------------------------------------------------------------------------
    | ترجمة أسماء الموارد للعرض فقط (لا تُخزن في DB).
    */
    'resource_labels' => [
    'dashboard'    => ['en' => 'Dashboard',          'ar' => 'لوحة التحكم'],
    'areas'        => ['en' => 'Areas',         'ar' => 'المناطق'],
    'districts'    => ['en' => 'Districts',     'ar' => 'المديريات'],
    'governorates' => ['en' => 'Governorates',  'ar' => 'المحافظات'],
    'categories'   => ['en' => 'Categories',    'ar' => 'الاقسام'],
    'tags'         => ['en' => 'Tags',          'ar' => 'الوسوم'],
    'products'     => ['en' => 'Products',      'ar' => 'المنتجات'],
    'offers'       => ['en' => 'Offers',        'ar' => 'العروض'],
    'addresses'    => ['en' => 'Addresses',  'ar' => 'العناوين'],
    'users'        => ['en' => 'Users',         'ar' => 'المستخدمون'],
    'admins'       => ['en' => 'Admins',        'ar' => 'المشرفون'],
    'roles'        => ['en' => 'Roles',         'ar' => 'الأدوار'],
    'permissions'  => ['en' => 'Permissions',   'ar' => 'الصلاحيات'],
    'profile'      => ['en' => 'Profile',            'ar' => 'الملف الشخصي'],
    'activitylogs'  => ['en' => 'Activity Logs',       'ar' => 'سجل التغييرات'],

    ],

    /*
    |--------------------------------------------------------------------------
    | Action Labels (واجهة المستخدم)
    |--------------------------------------------------------------------------
    | ترجمة أسماء الأفعال للعرض فقط (لا تُخزن في DB).
    */
    'action_labels' => [
        'view'   => ['en' => 'View',   'ar' => 'عرض'],
        'create' => ['en' => 'Create', 'ar' => 'إنشاء'],
        'update' => ['en' => 'Update', 'ar' => 'تعديل'],
        'delete' => ['en' => 'Delete', 'ar' => 'حذف'],
    ],

];
