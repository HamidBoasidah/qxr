# 📮 دليل اختبار Order Creation API باستخدام Postman

## 📥 استيراد الملفات

### 1. استيراد Collection
1. افتح Postman
2. اضغط على **Import** في الزاوية العلوية اليسرى
3. اسحب ملف `Order_Creation_API.postman_collection.json` أو اضغط **Choose Files**
4. اضغط **Import**

### 2. استيراد Environment
1. اضغط على **Import** مرة أخرى
2. اسحب ملف `Order_Creation_API.postman_environment.json`
3. اضغط **Import**
4. اختر Environment "Order Creation API - Local" من القائمة المنسدلة في الزاوية العلوية اليمنى

## ⚙️ إعداد Environment Variables

قبل البدء، تأكد من تحديث المتغيرات التالية في Environment:

| Variable | Description | Example Value |
|----------|-------------|---------------|
| `base_url` | رابط الـ API | `http://localhost:8000/api` |
| `auth_token` | سيتم ملؤه تلقائيًا بعد Login | - |
| `company_id` | ID الشركة | `1` |
| `product_id_1` | ID المنتج الأول | `1` |
| `product_id_2` | ID المنتج الثاني | `2` |
| `offer_id_percentage` | ID عرض الخصم النسبي | `1` |
| `offer_id_fixed` | ID عرض الخصم الثابت | `2` |
| `offer_id_bonus` | ID عرض البونص | `3` |

## 🚀 البدء في الاختبار

### الخطوة 1: تسجيل الدخول
1. افتح مجلد **"0. Setup - Authentication"**
2. شغّل request **"Login as Customer"**
3. سيتم حفظ الـ token تلقائيًا في Environment

> **ملاحظة:** تأكد من وجود مستخدم بنوع `customer` في قاعدة البيانات

### الخطوة 2: اختبار السيناريوهات

## 📋 السيناريوهات المتاحة

### 1️⃣ Success Scenarios (سيناريوهات النجاح)
اختبارات للطلبات الصحيحة التي يجب أن تنجح:

- **1.1 Order Without Offers** - طلب بدون عروض
- **1.2 Order With Percentage Discount** - طلب مع خصم نسبة مئوية 10%
- **1.3 Order With Fixed Discount** - طلب مع خصم ثابت 100 ريال
- **1.4 Order With Bonus Quantity** - طلب مع بونص 20 قطعة
- **1.5 Order With Multiple Items** - طلب متعدد المنتجات مع عروض مختلفة

**النتيجة المتوقعة:** HTTP 201 Created

---

### 2️⃣ Validation Error Scenarios (أخطاء التحقق)
اختبارات للطلبات غير الصحيحة:

- **2.1 Missing Required Fields** - حقول مطلوبة ناقصة
- **2.2 Invalid Quantity (Zero)** - كمية صفر
- **2.3 Duplicate Products** - منتجات مكررة
- **2.4 Invalid Bonus Index** - index خارج النطاق

**النتيجة المتوقعة:** HTTP 422 Unprocessable Entity

---

### 3️⃣ Stale Data Scenarios (بيانات قديمة)
اختبارات للبيانات التي تغيرت منذ أن جلبها الكلاينت:

- **3.1 Price Changed** - السعر تغير
- **3.2 Expired Offer** - عرض منتهي الصلاحية

**النتيجة المتوقعة:** HTTP 409 Conflict

---

### 4️⃣ Tampering Detection Scenarios (كشف التلاعب)
اختبارات لمحاولات التلاعب بالحسابات:

- **4.1 Wrong Discount Calculation** - حساب خصم خاطئ
- **4.2 Wrong Bonus Quantity** - كمية بونص خاطئة
- **4.3 Discount With Bonus** - خصم مع بونص (انتهاك)
- **4.4 Quantity Below Minimum** - كمية أقل من الحد الأدنى

**النتيجة المتوقعة:** HTTP 422 Unprocessable Entity (مع تفاصيل الخطأ)

---

### 5️⃣ Authorization Scenarios (الصلاحيات)
اختبارات للصلاحيات والتفويض:

- **5.1 Unauthenticated Request** - طلب بدون تسجيل دخول
- **5.2 Product From Wrong Company** - منتج من شركة خاطئة

**النتيجة المتوقعة:** 
- HTTP 401 Unauthorized (بدون token)
- HTTP 403 Forbidden (منتج من شركة أخرى)

---

### 6️⃣ Edge Cases & Special Scenarios (حالات خاصة)
اختبارات لحالات خاصة:

- **6.1 Large Order** - طلب كبير متعدد المنتجات
- **6.2 Minimum Quantity Order** - طلب بالحد الأدنى
- **6.3 Fractional Multiplier** - كمية 1500 مع حد أدنى 1000
- **6.4 Rounding Edge Case** - اختبار التقريب ROUND_HALF_UP

**النتيجة المتوقعة:** HTTP 201 Created (مع حسابات صحيحة)

---

## 🔍 فهم الـ Response

### Success Response (HTTP 201)
```json
{
    "success": true,
    "message": "Order created successfully",
    "data": {
        "order": {
            "id": 1,
            "order_no": "ORD-20260217103045-A3F2",
            "status": "pending",
            "submitted_at": "2026-02-17T10:30:45+00:00",
            "notes": "ملاحظات العميل",
            "items": [
                {
                    "id": 1,
                    "product_id": 1,
                    "product_name": "Aspirin",
                    "qty": 1000,
                    "unit_price": 10.00,
                    "discount_amount": 1000.00,
                    "final_total": 9000.00,
                    "selected_offer_id": 1,
                    "bonuses": []
                }
            ],
            "subtotal": 10000.00,
            "total_discount": 1000.00,
            "final_total": 9000.00
        }
    }
}
```

### Error Response (HTTP 409 - Stale Data)
```json
{
    "success": false,
    "message": "Price for product 1 has changed. Please refresh and try again."
}
```

### Error Response (HTTP 422 - Tampering)
```json
{
    "success": false,
    "message": "تم اكتشاف تلاعب في البيانات",
    "errors": [
        "Discount mismatch for product 1: expected 1000.00, got 5000.00"
    ]
}
```

## 📊 نصائح للاختبار

### 1. اختبار متسلسل
ابدأ بالسيناريوهات الناجحة أولاً للتأكد من أن الـ API يعمل، ثم انتقل للأخطاء.

### 2. مراقبة Database
استخدم أداة مثل TablePlus أو phpMyAdmin لمراقبة الجداول:
- `orders`
- `order_items`
- `order_item_bonuses`
- `order_status_logs`

### 3. استخدام Console
افتح Postman Console (View → Show Postman Console) لرؤية تفاصيل الـ requests والـ responses.

### 4. تشغيل Collection Runner
يمكنك تشغيل كل السيناريوهات دفعة واحدة:
1. اضغط على Collection
2. اضغط **Run**
3. اختر السيناريوهات التي تريد تشغيلها
4. اضغط **Run Order Creation API**

## 🐛 استكشاف الأخطاء

### مشكلة: HTTP 401 Unauthorized
**الحل:** تأكد من تشغيل request "Login as Customer" أولاً

### مشكلة: HTTP 404 Not Found
**الحل:** تأكد من:
- الـ server يعمل (`php artisan serve`)
- الـ `base_url` صحيح في Environment

### مشكلة: Product not found
**الحل:** تأكد من وجود المنتجات في قاعدة البيانات وتحديث IDs في Environment

### مشكلة: Offer not found
**الحل:** تأكد من وجود العروض في قاعدة البيانات:
```sql
-- إنشاء عرض خصم نسبي 10%
INSERT INTO offers (company_user_id, scope, status, title, reward_type, reward_value) 
VALUES (1, 'public', 'active', 'Percentage Discount 10%', 'percentage_discount', 10);

-- إنشاء offer_item
INSERT INTO offer_items (offer_id, product_id, min_qty) 
VALUES (1, 1, 1000);
```

## 📝 ملاحظات مهمة

1. **0-based indexing**: الـ `order_item_index` يبدأ من 0
2. **Rounding**: كل الحسابات المالية تستخدم ROUND_HALF_UP
3. **Single offer**: كل منتج يمكن أن يكون له عرض واحد فقط
4. **Exclusivity**: الخصم والبونص لا يجتمعان في نفس المنتج

## 🎯 الخلاصة

هذا الـ Collection يغطي:
- ✅ 6 أقسام رئيسية
- ✅ 20+ سيناريو اختبار
- ✅ جميع حالات النجاح والفشل
- ✅ اختبارات التحقق والتلاعب
- ✅ حالات خاصة وحدية

استمتع بالاختبار! 🚀
