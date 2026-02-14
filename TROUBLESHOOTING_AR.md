# حل مشكلة "العرض المطلوب غير موجود"

## ✅ تم إصلاح المشكلة الرئيسية

تم إصلاح ترتيب الـ routes. الآن المسارات بالترتيب الصحيح:

```php
// ✅ الصحيح: المسارات المحددة قبل apiResource
Route::get('offers/public', ...);           // يطابق أولاً
Route::get('offers/public/{id}', ...);      // يطابق ثانياً
Route::apiResource('offers', ...);          // يطابق أخيراً
```

---

## 🔍 التحقق من المشكلة الحالية

الآن المشكلة قد تكون أحد الأسباب التالية:

### 1. لا توجد عروض في قاعدة البيانات

**التحقق:**
```bash
php artisan tinker
```

ثم:
```php
\App\Models\Offer::count();
// إذا كانت النتيجة 0، لا توجد عروض
```

**الحل:** أنشئ عرض تجريبي (انظر القسم التالي)

---

### 2. لا توجد عروض تطابق الشروط

الـ endpoint `GET /api/offers/public` يعرض فقط العروض التي تطابق:
- ✅ `scope = 'public'`
- ✅ `status = 'active'`
- ✅ `start_at <= now()` أو `start_at = null`
- ✅ `end_at >= now()` أو `end_at = null`

**التحقق:**
```bash
php artisan tinker
```

ثم:
```php
// عدد جميع العروض
\App\Models\Offer::count();

// عدد العروض العامة
\App\Models\Offer::where('scope', 'public')->count();

// عدد العروض النشطة
\App\Models\Offer::where('status', 'active')->count();

// عدد العروض التي تطابق جميع الشروط
\App\Models\Offer::where('scope', 'public')
    ->where('status', 'active')
    ->where(function ($q) {
        $q->whereNull('start_at')
            ->orWhere('start_at', '<=', now());
    })
    ->where(function ($q) {
        $q->whereNull('end_at')
            ->orWhere('end_at', '>=', now());
    })
    ->count();
```

---

## 🛠️ إنشاء عرض تجريبي

### الطريقة 1: عبر Tinker (سريع)

```bash
php artisan tinker
```

ثم:
```php
// احصل على ID شركة موجودة
$companyId = \App\Models\User::where('user_type', 'company')->first()->id;

// احصل على ID منتج موجود
$productId = \App\Models\Product::where('company_user_id', $companyId)->first()->id;

// أنشئ عرض
$offer = \App\Models\Offer::create([
    'company_user_id' => $companyId,
    'title' => 'عرض تجريبي',
    'description' => 'هذا عرض للاختبار',
    'scope' => 'public',
    'status' => 'active',
    'start_at' => now()->subDays(1),
    'end_at' => now()->addDays(30),
]);

// أضف عنصر للعرض
$offer->items()->create([
    'product_id' => $productId,
    'min_qty' => 5,
    'reward_type' => 'discount_percent',
    'discount_percent' => 20.00,
]);

echo "تم إنشاء العرض بنجاح! ID: " . $offer->id;
```

---

### الطريقة 2: عبر API (باستخدام Postman)

1. **سجّل الدخول:**
   ```
   POST /api/login
   {
       "email": "company@example.com",
       "password": "password"
   }
   ```

2. **احصل على منتجاتك:**
   ```
   GET /api/products/mine
   ```
   انسخ `id` لمنتج

3. **أنشئ عرض:**
   ```
   POST /api/offers
   {
       "title": "عرض تجريبي",
       "description": "للاختبار",
       "scope": "public",
       "status": "active",
       "start_at": "2026-02-01",
       "end_at": "2026-12-31",
       "items": [
           {
               "product_id": 1,
               "min_qty": 5,
               "reward_type": "discount_percent",
               "discount_percent": 20.00
           }
       ],
       "targets": []
   }
   ```

4. **اختبر العروض العامة:**
   ```
   GET /api/offers/public
   ```

---

## 🧪 اختبار الـ Routes

### 1. تحقق من الـ routes:
```bash
php artisan route:list --path=api/offers
```

يجب أن ترى:
```
GET|HEAD  api/offers/public ........... Api\OfferController@publicIndex
GET|HEAD  api/offers/public/{id} ...... Api\OfferController@publicShow
GET|HEAD  api/offers .................. offers.index
POST      api/offers .................. offers.store
...
```

### 2. اختبر مباشرة من المتصفح أو cURL:

**بدون مصادقة (يجب أن يفشل):**
```bash
curl http://localhost:8000/api/offers/public
```
النتيجة المتوقعة: `{"message":"Unauthenticated."}`

**مع مصادقة:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:8000/api/offers/public
```

---

## 📊 التحقق من البيانات

### عرض جميع العروض:
```bash
php artisan tinker
```

```php
\App\Models\Offer::with('items', 'company')->get()->map(function($o) {
    return [
        'id' => $o->id,
        'title' => $o->title,
        'scope' => $o->scope,
        'status' => $o->status,
        'start_at' => $o->start_at,
        'end_at' => $o->end_at,
        'items_count' => $o->items->count(),
        'company' => $o->company->first_name ?? 'N/A',
    ];
});
```

---

## 🔧 إصلاح العروض الموجودة

إذا كانت لديك عروض لكنها لا تظهر، قد تحتاج لتحديثها:

```bash
php artisan tinker
```

```php
// تحديث جميع العروض لتكون عامة ونشطة
\App\Models\Offer::query()->update([
    'scope' => 'public',
    'status' => 'active',
    'start_at' => now()->subDays(1),
    'end_at' => now()->addDays(30),
]);

echo "تم تحديث " . \App\Models\Offer::count() . " عرض";
```

---

## ✅ الخطوات النهائية

1. **تأكد من وجود عروض:**
   ```bash
   php artisan tinker
   \App\Models\Offer::where('scope', 'public')->where('status', 'active')->count();
   ```

2. **سجّل الدخول في Postman:**
   ```
   Authentication → Login
   ```

3. **اختبر العروض العامة:**
   ```
   Public Offers → Get Public Offers List
   ```

4. **إذا نجح:** يجب أن ترى قائمة بالعروض ✅

5. **إذا فشل:** راجع الخطوات أعلاه

---

## 🆘 إذا استمرت المشكلة

### تحقق من الـ logs:
```bash
tail -f storage/logs/laravel.log
```

### تفعيل Debug Mode:
في `.env`:
```
APP_DEBUG=true
```

### اختبر الـ Controller مباشرة:
```bash
php artisan tinker
```

```php
$controller = new \App\Http\Controllers\Api\OfferController();
$request = new \Illuminate\Http\Request();
$request->setUserResolver(function() {
    return \App\Models\User::where('user_type', 'company')->first();
});

$offers = new \App\Repositories\OfferRepository(new \App\Models\Offer());
$response = $controller->publicIndex($request, $offers);
dd($response->getData());
```

---

## 📝 ملخص الحل

1. ✅ تم إصلاح ترتيب الـ routes
2. ✅ المسارات المحددة الآن قبل apiResource
3. ✅ يجب التأكد من وجود عروض تطابق الشروط
4. ✅ يجب تسجيل الدخول قبل الاختبار

**الآن جرّب مرة أخرى!** 🚀
