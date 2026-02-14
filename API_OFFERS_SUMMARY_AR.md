# ملخص API العروض

## نظرة عامة
تم إنشاء API كامل لإدارة العروض مع نوعين من الـ endpoints:

### 1. Endpoints عامة (بدون مصادقة)
للسماح لجميع المستخدمين بمشاهدة العروض العامة النشطة

### 2. Endpoints خاصة بالشركات (تتطلب مصادقة)
للسماح للشركات بإدارة عروضها بالكامل (إنشاء، تعديل، حذف)

---

## الملفات التي تم إنشاؤها/تعديلها

### 1. Controller الجديد
**الملف:** `app/Http/Controllers/Api/OfferController.php`

**المميزات:**
- استخدام نفس البنية المتبعة في `ProductController`
- استخدام Traits: `SuccessResponse`, `ExceptionHandler`, `CanFilter`
- استخدام نفس ملفات Request الموجودة في `Company` namespace
- استخدام `OfferService` و `OfferRepository` الموجودين
- استخدام `OfferDTO` للتحويل
- استخدام Policy للتحقق من الصلاحيات

**الدوال:**

#### Endpoints عامة (بدون مصادقة):
1. `publicIndex()` - قائمة العروض العامة النشطة
2. `publicShow($id)` - تفاصيل عرض عام محدد

#### Endpoints الشركات (تتطلب مصادقة):
3. `index()` - قائمة عروض الشركة المسجلة
4. `store()` - إنشاء عرض جديد
5. `show($id)` - تفاصيل عرض محدد للشركة
6. `update($id)` - تحديث عرض
7. `destroy($id)` - حذف عرض

---

### 2. Routes
**الملف:** `routes/api.php`

**المسارات المضافة:**

#### Endpoints عامة:
```php
GET  /api/offers/public        // قائمة العروض العامة
GET  /api/offers/public/{id}   // تفاصيل عرض عام
```

#### Endpoints الشركات (داخل middleware auth:sanctum):
```php
GET    /api/offers           // قائمة عروض الشركة
POST   /api/offers           // إنشاء عرض جديد
GET    /api/offers/{id}      // تفاصيل عرض
PUT    /api/offers/{id}      // تحديث عرض
DELETE /api/offers/{id}      // حذف عرض
```

---

## كيفية الاستخدام

### 1. للمستخدمين العاديين (بدون تسجيل دخول)

#### عرض جميع العروض العامة النشطة:
```bash
GET /api/offers/public?per_page=10
```

**الفلاتر التلقائية:**
- فقط العروض العامة (`scope = 'public'`)
- فقط العروض النشطة (`status = 'active'`)
- فقط العروض الحالية (ضمن تاريخ البداية والنهاية)

#### عرض تفاصيل عرض محدد:
```bash
GET /api/offers/public/1
```

---

### 2. للشركات (تتطلب تسجيل دخول)

#### تسجيل الدخول أولاً:
```bash
POST /api/login
{
  "email": "company@example.com",
  "password": "password"
}
```

**الاستجابة:**
```json
{
  "token": "1|xxxxxxxxxxxxx"
}
```

#### استخدام الـ Token في الطلبات:
```
Authorization: Bearer 1|xxxxxxxxxxxxx
```

---

### 3. عمليات الشركة

#### أ. عرض قائمة عروض الشركة:
```bash
GET /api/offers
Authorization: Bearer {token}
```

**مع فلاتر:**
```bash
GET /api/offers?per_page=10&scope=public&status=active&search=صيف
```

#### ب. إنشاء عرض جديد:
```bash
POST /api/offers
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "عرض الصيف الكبير",
  "description": "خصومات تصل إلى 50%",
  "scope": "public",
  "status": "draft",
  "start_at": "2026-02-01",
  "end_at": "2026-03-31",
  "items": [
    {
      "product_id": 10,
      "min_qty": 5,
      "reward_type": "discount_percent",
      "discount_percent": 20.00
    }
  ],
  "targets": []
}
```

#### ج. تحديث عرض:
```bash
PUT /api/offers/1
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "عرض الصيف المحدث",
  "status": "active"
}
```

#### د. حذف عرض:
```bash
DELETE /api/offers/1
Authorization: Bearer {token}
```

---

## أنواع المكافآت (Reward Types)

### 1. خصم نسبة مئوية (discount_percent)
```json
{
  "reward_type": "discount_percent",
  "discount_percent": 20.00
}
```
**مثال:** اشتري 5 قطع واحصل على خصم 20%

### 2. خصم مبلغ ثابت (discount_fixed)
```json
{
  "reward_type": "discount_fixed",
  "discount_fixed": 50.00
}
```
**مثال:** اشتري 10 قطع واحصل على خصم 50 ريال

### 3. كمية مجانية (bonus_qty)
```json
{
  "reward_type": "bonus_qty",
  "bonus_qty": 2,
  "bonus_product_id": 12
}
```
**مثال:** اشتري 10 من المنتج أ واحصل على 2 مجاناً من المنتج ب

---

## أنواع الاستهداف (Target Types)

### 1. عميل محدد (customer)
```json
{
  "target_type": "customer",
  "target_id": 123
}
```

### 2. فئة عملاء (customer_category)
```json
{
  "target_type": "customer_category",
  "target_id": 5
}
```

### 3. وسم عملاء (customer_tag)
```json
{
  "target_type": "customer_tag",
  "target_id": 8
}
```

---

## الأمان والصلاحيات

### 1. التحقق من نوع المستخدم
- فقط المستخدمين من نوع `company` يمكنهم إنشاء/تعديل/حذف العروض
- يتم التحقق عبر `OfferPolicy`

### 2. التحقق من الملكية
- الشركة يمكنها فقط إدارة عروضها الخاصة
- عند محاولة الوصول لعرض غير مملوك، يتم إرجاع 404 (وليس 403 لأسباب أمنية)

### 3. التحقق من المنتجات
- يتم التحقق من أن المنتجات المستخدمة في العرض تنتمي للشركة نفسها
- يتم ذلك في `BaseOfferRequest::validateProductOwnership()`

### 4. التحقق من الاستهداف
- إذا كان العرض خاص (`scope = 'private'`)، يجب تحديد مستهدف واحد على الأقل
- يتم التحقق من صحة الـ targets في `BaseOfferRequest::validateTargets()`

---

## الفروقات عن Company Dashboard

### التشابهات:
1. نفس ملفات Request (`Company\StoreOfferRequest`, `Company\UpdateOfferRequest`)
2. نفس `OfferService` و `OfferRepository`
3. نفس `OfferDTO`
4. نفس `OfferPolicy`
5. نفس قواعد التحقق (Validation)

### الاختلافات:
1. **الاستجابات:**
   - Company Dashboard: يستخدم `Inertia::render()` لإرجاع صفحات Vue
   - API: يستخدم `SuccessResponse` trait لإرجاع JSON

2. **المصادقة:**
   - Company Dashboard: `auth:web` middleware
   - API: `auth:sanctum` middleware

3. **Endpoints إضافية:**
   - API يحتوي على `publicIndex()` و `publicShow()` للعروض العامة
   - Company Dashboard لا يحتاج هذه الـ endpoints

4. **Error Handling:**
   - API: يستخدم `ExceptionHandler` trait
   - Company Dashboard: يعتمد على Laravel's default error handling

---

## الاختبار

### 1. باستخدام Postman

#### أ. تسجيل الدخول:
```
POST http://localhost:8000/api/login
Body (JSON):
{
  "email": "company@example.com",
  "password": "password"
}
```

#### ب. نسخ الـ Token من الاستجابة

#### ج. اختبار Endpoints:
```
GET http://localhost:8000/api/offers/public
GET http://localhost:8000/api/offers
Headers: Authorization: Bearer {token}
```

### 2. باستخدام cURL

#### عرض العروض العامة:
```bash
curl -X GET http://localhost:8000/api/offers/public
```

#### إنشاء عرض جديد:
```bash
curl -X POST http://localhost:8000/api/offers \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "عرض تجريبي",
    "scope": "public",
    "status": "draft",
    "items": [
      {
        "product_id": 1,
        "min_qty": 5,
        "reward_type": "discount_percent",
        "discount_percent": 20
      }
    ]
  }'
```

---

## ملاحظات مهمة

1. **التاريخ:** يتم إدخال التواريخ بصيغة `YYYY-MM-DD` وإرجاعها بصيغة `YYYY-MM-DD HH:MM:SS`

2. **الترقيم:** جميع endpoints القوائم تدعم الترقيم عبر `per_page` parameter

3. **الفلترة:** قائمة عروض الشركة تدعم الفلترة حسب `scope`, `status`, والبحث النصي

4. **استبدال كامل:** عند تحديث `items` أو `targets`، يتم استبدالها بالكامل (وليس دمجها)

5. **Soft Delete:** العروض تستخدم soft delete، لذا لا يتم حذفها نهائياً من قاعدة البيانات

6. **العلاقات:** يتم تحميل العلاقات بشكل مختلف حسب الـ endpoint:
   - `index()`: تحميل خفيف مع counts فقط
   - `show()`: تحميل كامل مع جميع العلاقات

---

## الخلاصة

تم إنشاء API كامل ومتكامل لإدارة العروض يتبع نفس البنية والأسلوب المستخدم في `ProductController`، مع:

✅ Endpoints عامة للجميع (بدون مصادقة)
✅ Endpoints خاصة للشركات (مع مصادقة)
✅ استخدام نفس الـ Services والـ Repositories الموجودة
✅ استخدام نفس ملفات Request والـ Validation
✅ استخدام Policy للتحقق من الصلاحيات
✅ استخدام DTO للتحويل
✅ توثيق كامل بالعربية والإنجليزية
✅ أمثلة عملية للاستخدام
