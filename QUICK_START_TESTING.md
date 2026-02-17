# 🚀 Quick Start - اختبار Order Creation API

## الخطوات السريعة للبدء

### 1️⃣ إعداد قاعدة البيانات

```bash
# شغّل الـ migrations
php artisan migrate

# أدخل البيانات التجريبية
mysql -u root -p your_database < test_data_setup.sql
```

### 2️⃣ تشغيل الـ Server

```bash
php artisan serve
```

### 3️⃣ استيراد Postman Collection

1. افتح Postman
2. Import → `Order_Creation_API.postman_collection.json`
3. Import → `Order_Creation_API.postman_environment.json`
4. اختر Environment "Order Creation API - Local"

### 4️⃣ تحديث Environment Variables

بعد تشغيل `test_data_setup.sql`، ستحصل على IDs. حدّث المتغيرات في Postman:

```
company_id: [من نتيجة SQL]
product_id_1: [من نتيجة SQL]
product_id_2: [من نتيجة SQL]
offer_id_percentage: [من نتيجة SQL]
offer_id_fixed: [من نتيجة SQL]
offer_id_bonus: [من نتيجة SQL]
```

### 5️⃣ تسجيل الدخول

شغّل request: **"0. Setup - Authentication → Login as Customer"**

```json
{
    "email": "customer@example.com",
    "password": "password"
}
```

سيتم حفظ الـ token تلقائيًا! ✅

### 6️⃣ اختبر أول طلب

شغّل request: **"1. Success Scenarios → 1.1 Order Without Offers"**

النتيجة المتوقعة: HTTP 201 Created 🎉

---

## 📊 السيناريوهات الأساسية للاختبار

### ✅ سيناريوهات النجاح (يجب أن تعطي 201)

1. **Order Without Offers** - طلب عادي بدون عروض
2. **Order With Percentage Discount** - طلب مع خصم 10%
3. **Order With Fixed Discount** - طلب مع خصم 100 ريال
4. **Order With Bonus** - طلب مع بونص 20 قطعة
5. **Multiple Items** - طلب متعدد المنتجات

### ❌ سيناريوهات الفشل (يجب أن تعطي أخطاء)

1. **Missing Fields** → 422
2. **Invalid Quantity** → 422
3. **Duplicate Products** → 422
4. **Price Changed** → 409
5. **Wrong Calculation** → 422
6. **Unauthenticated** → 401

---

## 🔍 التحقق من النتائج

### في Postman
راقب الـ Response:
- Status Code
- Response Body
- Response Time

### في Database
تحقق من الجداول:

```sql
-- آخر طلب تم إنشاؤه
SELECT * FROM orders ORDER BY id DESC LIMIT 1;

-- منتجات الطلب
SELECT * FROM order_items WHERE order_id = [last_order_id];

-- البونصات
SELECT * FROM order_item_bonuses WHERE order_item_id IN (
    SELECT id FROM order_items WHERE order_id = [last_order_id]
);

-- سجل الحالات
SELECT * FROM order_status_logs WHERE order_id = [last_order_id];
```

---

## 🐛 حل المشاكل الشائعة

### مشكلة: 401 Unauthorized
```
الحل: شغّل Login request أولاً
```

### مشكلة: 404 Product not found
```
الحل: تأكد من تشغيل test_data_setup.sql
```

### مشكلة: 409 Price Changed
```
الحل: السعر في الطلب مختلف عن قاعدة البيانات
تحقق من base_price في جدول products
```

### مشكلة: 422 Calculation Mismatch
```
الحل: الحسابات خاطئة (هذا متعمد لاختبار anti-tamper)
استخدم السيناريوهات الصحيحة من القسم 1
```

---

## 📝 أمثلة سريعة

### مثال 1: طلب بسيط بدون عروض
```json
{
    "company_id": 1,
    "notes": "طلب تجريبي",
    "order_items": [
        {
            "product_id": 1,
            "qty": 100,
            "unit_price_snapshot": 10.00,
            "discount_amount_snapshot": 0.00,
            "final_line_total_snapshot": 1000.00,
            "selected_offer_id": null
        }
    ],
    "order_item_bonuses": []
}
```

### مثال 2: طلب مع خصم 10%
```json
{
    "company_id": 1,
    "order_items": [
        {
            "product_id": 1,
            "qty": 1000,
            "unit_price_snapshot": 10.00,
            "discount_amount_snapshot": 1000.00,
            "final_line_total_snapshot": 9000.00,
            "selected_offer_id": 1
        }
    ]
}
```

### مثال 3: طلب مع بونص
```json
{
    "company_id": 1,
    "order_items": [
        {
            "product_id": 1,
            "qty": 1000,
            "unit_price_snapshot": 10.00,
            "discount_amount_snapshot": 0.00,
            "final_line_total_snapshot": 10000.00,
            "selected_offer_id": 3
        }
    ],
    "order_item_bonuses": [
        {
            "order_item_index": 0,
            "bonus_product_id": 1,
            "bonus_qty": 20
        }
    ]
}
```

---

## 🎯 نصائح للاختبار الفعال

1. **ابدأ بالبسيط**: اختبر السيناريوهات الناجحة أولاً
2. **راقب Database**: استخدم TablePlus أو phpMyAdmin
3. **استخدم Console**: Postman Console يساعد في debugging
4. **اختبر بالترتيب**: اتبع ترتيب الأقسام في Collection
5. **نظّف البيانات**: احذف الطلبات القديمة بين الاختبارات

```sql
-- حذف جميع الطلبات التجريبية
DELETE FROM order_status_logs;
DELETE FROM order_item_bonuses;
DELETE FROM order_items;
DELETE FROM orders;
```

---

## ✅ Checklist للاختبار الكامل

- [ ] تشغيل migrations
- [ ] إدخال test data
- [ ] استيراد Postman collection
- [ ] تحديث environment variables
- [ ] تسجيل دخول ناجح
- [ ] اختبار طلب بدون عروض
- [ ] اختبار طلب مع خصم نسبي
- [ ] اختبار طلب مع خصم ثابت
- [ ] اختبار طلب مع بونص
- [ ] اختبار طلب متعدد المنتجات
- [ ] اختبار أخطاء التحقق (422)
- [ ] اختبار البيانات القديمة (409)
- [ ] اختبار كشف التلاعب (422)
- [ ] اختبار الصلاحيات (401, 403)

---

## 📚 مراجع إضافية

- [POSTMAN_TESTING_GUIDE.md](POSTMAN_TESTING_GUIDE.md) - دليل شامل
- [test_data_setup.sql](test_data_setup.sql) - البيانات التجريبية
- [.kiro/specs/order-creation-api/](/.kiro/specs/order-creation-api/) - المواصفات الكاملة

---

**جاهز للاختبار؟ ابدأ الآن! 🚀**
