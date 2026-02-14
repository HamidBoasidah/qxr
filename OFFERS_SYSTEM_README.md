# نظام العروض (Offers System) - دليل سريع

## 📋 نظرة عامة

نظام عروض متكامل يسمح للشركات بإنشاء عروض ترويجية على منتجاتها مع استهداف عملاء محددين.

---

## 🏗️ المعمارية

```
Controller → Service → Repository → Model
     ↓
   Policy (Authorization)
     ↓
   Request (Validation)
     ↓
   DTO (Data Transfer)
```

---

## 📦 الجداول

### 1. offers
```sql
- id
- company_user_id (FK → users)
- scope (public/private)
- status (draft/active/paused/expired)
- title
- description
- start_at
- end_at
- timestamps
- soft_deletes
```

### 2. offer_items
```sql
- id
- offer_id (FK → offers)
- product_id (FK → products)
- min_qty
- reward_type (discount_percent/discount_fixed/bonus_qty)
- discount_percent
- discount_fixed
- bonus_product_id (FK → products)
- bonus_qty
- timestamps
```

### 3. offer_targets
```sql
- id
- offer_id (FK → offers)
- target_type (customer/customer_category/customer_tag)
- target_id
- timestamps
```

---

## 🔐 الأمان

### Policy Rules
```php
✅ viewAny: user_type === 'company'
✅ view: owner + company
✅ create: user_type === 'company'
✅ update: owner + company
✅ delete: owner + company
```

### Validation Rules
```php
✅ المنتجات يجب أن تكون ملك الشركة
✅ reward_type validation صارم
✅ private offers تحتاج targets
✅ targets validation مع type checking
```

---

## 🎯 أنواع العروض

### 1. Public Offers
- متاحة لجميع العملاء
- لا تحتاج targets

### 2. Private Offers
- مخصصة لعملاء محددين
- تحتاج targets (customer/category/tag)

---

## 💰 أنواع المكافآت

### 1. discount_percent
```php
'reward_type' => 'discount_percent',
'discount_percent' => 10.5,  // خصم 10.5%
```

### 2. discount_fixed
```php
'reward_type' => 'discount_fixed',
'discount_fixed' => 50.00,  // خصم 50 ريال
```

### 3. bonus_qty
```php
'reward_type' => 'bonus_qty',
'bonus_product_id' => 123,
'bonus_qty' => 2,  // منتج مجاني × 2
```

---

## 📝 أمثلة الاستخدام

### إنشاء عرض عام
```php
POST /admin/offers

{
    "title": "عرض الصيف",
    "description": "خصم 20% على جميع المنتجات",
    "scope": "public",
    "status": "active",
    "start_at": "2024-06-01",
    "end_at": "2024-08-31",
    "items": [
        {
            "product_id": 1,
            "min_qty": 1,
            "reward_type": "discount_percent",
            "discount_percent": 20
        }
    ]
}
```

### إنشاء عرض خاص
```php
POST /admin/offers

{
    "title": "عرض VIP",
    "scope": "private",
    "status": "active",
    "items": [
        {
            "product_id": 1,
            "min_qty": 5,
            "reward_type": "bonus_qty",
            "bonus_product_id": 2,
            "bonus_qty": 1
        }
    ],
    "targets": [
        {
            "target_type": "customer_category",
            "target_id": 3
        }
    ]
}
```

---

## 🔍 Query Scopes

### activeNow()
```php
// العروض الفعالة الآن
Offer::activeNow()->get();

// status = active
// start_at <= now (or null)
// end_at >= now (or null)
```

### forCompany()
```php
// عروض شركة محددة
Offer::forCompany($companyId)->get();
```

### public() / private()
```php
Offer::public()->get();
Offer::private()->get();
```

---

## 📊 الأداء

### Index (قائمة العروض)
```php
✅ Eager loading: company.companyProfile
✅ withCount: items, targets
✅ DTO: toIndexArray() (خفيف)
```

### Show/Edit (تفاصيل العرض)
```php
✅ Eager loading: company, items.product, items.bonusProduct, targets
✅ withCount: items, targets
✅ DTO: toArray() (كامل)
```

---

## 🛠️ الصيانة

### إضافة نوع مكافأة جديد

1. أضف في Migration:
```php
$table->enum('reward_type', [
    'discount_percent',
    'discount_fixed',
    'bonus_qty',
    'new_type'  // ✅
]);
```

2. أضف في BaseOfferRequest:
```php
protected function validateRewardTypes(Validator $v, array $items): void
{
    // ... existing code
    
    if ($rewardType === 'new_type') {
        // validation logic
    }
}
```

3. أضف في Rules:
```php
'items.*.reward_type' => [
    'required',
    'in:discount_percent,discount_fixed,bonus_qty,new_type'
],
```

---

## 🧪 الاختبارات

### Unit Tests
```bash
php artisan test --filter=OfferTest
```

### Feature Tests
```bash
php artisan test --filter=OfferControllerTest
```

### Policy Tests
```bash
php artisan test --filter=OfferPolicyTest
```

---

## 📚 الملفات الرئيسية

```
app/
├── Models/
│   ├── Offer.php
│   ├── OfferItem.php
│   └── OfferTarget.php
├── Policies/
│   └── OfferPolicy.php
├── Http/
│   ├── Controllers/Admin/
│   │   └── OfferController.php
│   └── Requests/
│       ├── BaseOfferRequest.php
│       ├── StoreOfferRequest.php
│       └── UpdateOfferRequest.php
├── Services/
│   └── OfferService.php
├── Repositories/
│   └── OfferRepository.php
└── DTOs/
    └── OfferDTO.php
```

---

## 🚀 الإطلاق

### قبل الإطلاق
```bash
# 1. تشغيل Migrations
php artisan migrate

# 2. Clear Cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 3. تشغيل الاختبارات
php artisan test

# 4. التحقق من Permissions
# تأكد من وجود:
# - offers.view
# - offers.create
# - offers.update
# - offers.delete
```

### بعد الإطلاق
```bash
# مراقبة الأداء
php artisan telescope:prune

# مراقبة الأخطاء
tail -f storage/logs/laravel.log
```

---

## 📞 الدعم

للمزيد من المعلومات، راجع:
- `OFFERS_SYSTEM_IMPROVEMENTS.md` - التحسينات المطبقة
- `OFFERS_SYSTEM_FINAL_SUMMARY.md` - الملخص النهائي

---

## ✅ Checklist للمطورين

- [ ] فهم المعمارية (Repository + Service + DTO)
- [ ] فهم Policy rules
- [ ] فهم Validation rules
- [ ] فهم أنواع المكافآت
- [ ] فهم الفرق بين public/private offers
- [ ] معرفة Query scopes المتاحة
- [ ] معرفة كيفية إضافة features جديدة
- [ ] تشغيل الاختبارات بنجاح

---

**تم بناء النظام بواسطة**: فريق التطوير
**آخر تحديث**: 2026-02-14
**الحالة**: ✅ جاهز للإنتاج
