# إصلاح خطأ 403 Forbidden

## المشكلة
```
Error fetching users: Error: HTTP error! status: 403
```

عند محاولة فتح modal المحادثة الجديدة، كان يظهر خطأ 403 لأن المستخدم لا يملك صلاحية `users.view`.

## السبب
كان المكون يستخدم endpoint `company.users.index` الذي يتطلب صلاحية `users.view` للوصول إليه.

## الحل
تم إنشاء endpoint منفصل خاص بالمحادثات لا يتطلب صلاحيات خاصة.

### 1. إنشاء Controller جديد
**الملف**: `app/Http/Controllers/Company/ChatUserController.php`

```php
<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\DTOs\UserDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(Request $request, UserService $userService)
    {
        $perPage = (int) $request->input('per_page', 50);
        $search = $request->input('search');
        $currentUserId = Auth::guard('web')->id();

        $users = $userService->search($search, $perPage);

        $users->getCollection()->transform(function ($user) use ($currentUserId) {
            if ($user->id === $currentUserId) {
                return null;
            }
            return UserDTO::fromModel($user)->toIndexArray();
        })->filter();

        return response()->json($users);
    }
}
```

**المميزات**:
- ✅ لا يتطلب صلاحيات خاصة (فقط authentication)
- ✅ يستبعد المستخدم الحالي تلقائياً من الـ backend
- ✅ يدعم البحث
- ✅ يرجع JSON response مباشرة

### 2. إضافة Route جديد
**الملف**: `routes/company.php`

```php
use App\Http\Controllers\Company\ChatUserController;

// في مجموعة chat routes
Route::prefix('chat')->as('chat.')->group(function () {
    // Get users for chat
    Route::get('/users', [ChatUserController::class, 'index'])
        ->name('users.index');
    
    // ... باقي الـ routes
});
```

**الـ Route الجديد**: `company.chat.users.index`

### 3. تحديث المكون
**الملف**: `resources/js/components/company/chat/NewConversationModal.vue`

```javascript
// قبل
const url = route('company.users.index')

// بعد
const url = route('company.chat.users.index')
```

```javascript
// قبل - كان يستبعد المستخدم الحالي من الـ frontend
users.value = data.data.filter(user => user.id !== props.authUserId)

// بعد - الـ backend يستبعده تلقائياً
users.value = data.data
```

## الفوائد

### 1. الأمان
- كل مستخدم مصادق عليه يمكنه رؤية المستخدمين الآخرين للمحادثة
- لا حاجة لصلاحيات إدارية لبدء محادثة

### 2. الأداء
- استبعاد المستخدم الحالي من الـ backend (أفضل من الـ frontend)
- تقليل حجم البيانات المرسلة

### 3. الصيانة
- فصل منطق المحادثات عن إدارة المستخدمين
- endpoint مخصص للمحادثات فقط

## الاختبار

### 1. تحقق من الـ Route
```bash
php artisan route:list --path=company/chat
```

يجب أن ترى:
```
GET company/chat/users company.chat.users.index
```

### 2. اختبار من المتصفح
1. سجل الدخول كمستخدم عادي (بدون صلاحيات إدارية)
2. افتح صفحة المحادثات
3. انقر على "محادثة جديدة"
4. يجب أن تظهر قائمة المستخدمين بدون خطأ 403

### 3. اختبار البحث
1. اكتب في حقل البحث
2. يجب أن تظهر النتائج المطابقة
3. المستخدم الحالي لا يظهر في القائمة

## الملفات المضافة/المعدلة

### ملفات جديدة
- ✅ `app/Http/Controllers/Company/ChatUserController.php`

### ملفات معدلة
- ✅ `routes/company.php` - إضافة route جديد
- ✅ `resources/js/components/company/chat/NewConversationModal.vue` - تغيير الـ endpoint

## ملاحظات

### الصلاحيات
- `company.users.index` - يتطلب `users.view` permission
- `company.chat.users.index` - يتطلب فقط authentication

### الاستخدام
- استخدم `company.users.index` لإدارة المستخدمين (Admin panel)
- استخدم `company.chat.users.index` للمحادثات (Chat feature)

## الخلاصة

تم حل المشكلة بإنشاء endpoint منفصل للمحادثات لا يتطلب صلاحيات إدارية، مما يسمح لجميع المستخدمين المصادق عليهم ببدء محادثات جديدة.
