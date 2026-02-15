# ميزة عرض العروض مع المنتجات في API

## 📋 نظرة عامة

تم إضافة ميزة جديدة لعرض معلومات العروض النشطة مع المنتجات في الـ API الخاص بالموبايل.

---

## ✅ ما تم إضافته

### 1. علاقات جديدة في Product Model

```php
// app/Models/Product.php

// العروض التي تحتوي على هذا المنتج
public function offerItems()
{
    return $this->hasMany(OfferItem::class, 'product_id');
}

// العروض النشطة الحالية على هذا المنتج
public function activeOffers()
{
    return $this->hasManyThrough(...)
        ->where('offers.status', 'active')
        ->where('offers.scope', 'public')
        ->where(/* تاريخ صالح */);
}
```

---

### 2. تحديث ProductDTO

#### إضافة حقل `active_offers`:
```php
public $active_offers; // معلومات العروض النشطة
```

#### إضافة method جديد `toMobileArray()`:
```php
public function toMobileArray(): array
{
    return [
        // ... جميع حقول المنتج
        'active_offers' => $this->active_offers, // ✅ فقط في toMobileArray
    ];
}
```

#### إضافة `formatActiveOffers()` method:
يقوم بتنسيق معلومات العروض بشكل مناسب للموبايل.

---

### 3. تحديث ProductController API

#### تحميل العلاقات الإضافية:
```php
protected function mobileWith(): array
{
    return [
        // ... العلاقات الأساسية
        'activeOffers:id,title,description,status,scope,start_at,end_at',
        'activeOffers.items:id,offer_id,product_id,min_qty,reward_type,...',
        'activeOffers.items.bonusProduct:id,name,main_image',
    ];
}
```

#### استخدام `toMobileArray()`:
```php
// في index(), mine(), show()
ProductDTO::fromModel($product)->toMobileArray()
```

---

## 📊 شكل البيانات المُرجعة

### مثال: منتج بدون عرض

```json
{
    "id": 1,
    "name": "منتج تجريبي",
    "base_price": 100.00,
    "active_offers": {
        "has_offer": false,
        "offers": []
    }
}
```

---

### مثال: منتج مع عرض خصم نسبة مئوية

```json
{
    "id": 1,
    "name": "منتج تجريبي",
    "base_price": 100.00,
    "active_offers": {
        "has_offer": true,
        "offers": [
            {
                "offer_id": 5,
                "offer_title": "عرض الصيف الكبير",
                "offer_description": "خصومات تصل إلى 50%",
                "min_qty": 5,
                "reward_type": "discount_percent",
                "discount_percent": 20.00,
                "discount_amount": 20.00,
                "final_price": 80.00,
                "start_at": "2026-02-01 00:00:00",
                "end_at": "2026-03-31 23:59:59"
            }
        ]
    }
}
```

---

### مثال: منتج مع عرض خصم ثابت

```json
{
    "id": 2,
    "name": "منتج آخر",
    "base_price": 200.00,
    "active_offers": {
        "has_offer": true,
        "offers": [
            {
                "offer_id": 6,
                "offer_title": "خصم 50 ريال",
                "offer_description": "على الطلبات الكبيرة",
                "min_qty": 10,
                "reward_type": "discount_fixed",
                "discount_fixed": 50.00,
                "final_price": 150.00,
                "start_at": "2026-02-14 00:00:00",
                "end_at": "2026-04-30 23:59:59"
            }
        ]
    }
}
```

---

### مثال: منتج مع عرض كمية مجانية

```json
{
    "id": 3,
    "name": "منتج ثالث",
    "base_price": 50.00,
    "active_offers": {
        "has_offer": true,
        "offers": [
            {
                "offer_id": 7,
                "offer_title": "اشتري 10 واحصل على 2 مجاناً",
                "offer_description": "عرض خاص",
                "min_qty": 10,
                "reward_type": "bonus_qty",
                "bonus_qty": 2,
                "bonus_product_id": 4,
                "bonus_product": {
                    "id": 4,
                    "name": "منتج المكافأة",
                    "image": "/storage/products/bonus.jpg"
                },
                "start_at": "2026-02-14 00:00:00",
                "end_at": "2026-05-31 23:59:59"
            }
        ]
    }
}
```

---

### مثال: منتج مع عدة عروض

```json
{
    "id": 4,
    "name": "منتج شائع",
    "base_price": 150.00,
    "active_offers": {
        "has_offer": true,
        "offers": [
            {
                "offer_id": 8,
                "offer_title": "عرض الكمية",
                "min_qty": 5,
                "reward_type": "discount_percent",
                "discount_percent": 15.00,
                "discount_amount": 22.50,
                "final_price": 127.50
            },
            {
                "offer_id": 9,
                "offer_title": "عرض الجملة",
                "min_qty": 20,
                "reward_type": "discount_percent",
                "discount_percent": 30.00,
                "discount_amount": 45.00,
                "final_price": 105.00
            }
        ]
    }
}
```

---

## 🔍 شروط العروض النشطة

العرض يُعتبر نشط إذا:
1. ✅ `status = 'active'`
2. ✅ `scope = 'public'`
3. ✅ `start_at <= now()` أو `start_at = null`
4. ✅ `end_at >= now()` أو `end_at = null`

---

## 📱 استخدام في الموبايل

### 1. عرض قائمة المنتجات مع العروض

```http
GET /api/products?per_page=20
Authorization: Bearer {token}
```

**الاستجابة:**
```json
{
    "success": true,
    "data": {
        "data": [
            {
                "id": 1,
                "name": "منتج 1",
                "base_price": 100.00,
                "active_offers": {
                    "has_offer": true,
                    "offers": [...]
                }
            },
            {
                "id": 2,
                "name": "منتج 2",
                "base_price": 200.00,
                "active_offers": {
                    "has_offer": false,
                    "offers": []
                }
            }
        ]
    }
}
```

---

### 2. عرض تفاصيل منتج واحد

```http
GET /api/products/1
Authorization: Bearer {token}
```

**الاستجابة:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "منتج تجريبي",
        "description": "وصف المنتج",
        "base_price": 100.00,
        "images": [...],
        "active_offers": {
            "has_offer": true,
            "offers": [
                {
                    "offer_id": 5,
                    "offer_title": "عرض الصيف",
                    "min_qty": 5,
                    "reward_type": "discount_percent",
                    "discount_percent": 20.00,
                    "final_price": 80.00
                }
            ]
        }
    }
}
```

---

## 🎨 عرض في UI الموبايل

### مثال React Native / Flutter:

```javascript
// عرض المنتج مع العرض
function ProductCard({ product }) {
    const hasOffer = product.active_offers?.has_offer;
    const offers = product.active_offers?.offers || [];
    
    return (
        <View>
            <Text>{product.name}</Text>
            
            {hasOffer && offers.length > 0 && (
                <View style={styles.offerBadge}>
                    <Text>🎉 عرض خاص</Text>
                    {offers.map(offer => (
                        <View key={offer.offer_id}>
                            <Text>{offer.offer_title}</Text>
                            
                            {offer.reward_type === 'discount_percent' && (
                                <>
                                    <Text style={styles.oldPrice}>
                                        {product.base_price} ريال
                                    </Text>
                                    <Text style={styles.newPrice}>
                                        {offer.final_price} ريال
                                    </Text>
                                    <Text style={styles.discount}>
                                        خصم {offer.discount_percent}%
                                    </Text>
                                </>
                            )}
                            
                            {offer.reward_type === 'discount_fixed' && (
                                <>
                                    <Text style={styles.oldPrice}>
                                        {product.base_price} ريال
                                    </Text>
                                    <Text style={styles.newPrice}>
                                        {offer.final_price} ريال
                                    </Text>
                                    <Text style={styles.discount}>
                                        وفّر {offer.discount_fixed} ريال
                                    </Text>
                                </>
                            )}
                            
                            {offer.reward_type === 'bonus_qty' && (
                                <Text style={styles.bonus}>
                                    اشتري {offer.min_qty} واحصل على {offer.bonus_qty} مجاناً
                                </Text>
                            )}
                            
                            <Text style={styles.minQty}>
                                الحد الأدنى: {offer.min_qty} قطعة
                            </Text>
                        </View>
                    ))}
                </View>
            )}
            
            {!hasOffer && (
                <Text>{product.base_price} ريال</Text>
            )}
        </View>
    );
}
```

---

## 🔧 ملاحظات تقنية

### 1. الأداء
- العلاقات يتم تحميلها بـ Eager Loading لتجنب N+1 queries
- يتم تحميل العروض النشطة فقط (مع الشروط)
- البيانات منسقة في الـ DTO لتقليل المعالجة في الـ Frontend

### 2. الفلترة
- العروض الخاصة (private) لا تظهر
- العروض المنتهية أو غير النشطة لا تظهر
- فقط العروض ضمن التاريخ الصالح تظهر

### 3. التوافقية
- `toArray()` و `toIndexArray()` لم يتغيرا
- فقط `toMobileArray()` يحتوي على معلومات العروض
- لا يؤثر على الـ endpoints الأخرى

---

## 🧪 اختبار الميزة

### 1. إنشاء عرض تجريبي:

```bash
php artisan tinker
```

```php
$company = \App\Models\User::where('user_type', 'company')->first();
$product = \App\Models\Product::where('company_user_id', $company->id)->first();

$offer = \App\Models\Offer::create([
    'company_user_id' => $company->id,
    'title' => 'عرض تجريبي',
    'description' => 'خصم 20%',
    'scope' => 'public',
    'status' => 'active',
    'start_at' => now()->subDays(1),
    'end_at' => now()->addDays(30),
]);

$offer->items()->create([
    'product_id' => $product->id,
    'min_qty' => 5,
    'reward_type' => 'discount_percent',
    'discount_percent' => 20.00,
]);

echo "تم إنشاء العرض بنجاح!";
```

---

### 2. اختبار الـ API:

```bash
# تسجيل الدخول
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# جلب المنتجات مع العروض
curl -X GET http://localhost:8000/api/products \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📝 الخلاصة

### ما تم إضافته:
✅ علاقة `activeOffers` في Product Model
✅ حقل `active_offers` في ProductDTO
✅ method جديد `toMobileArray()` في ProductDTO
✅ method جديد `mobileWith()` في ProductController
✅ تحديث `index()`, `mine()`, `show()` لاستخدام `toMobileArray()`

### الفوائد:
✅ المنتجات تعرض العروض النشطة تلقائياً
✅ حساب السعر النهائي تلقائياً
✅ دعم جميع أنواع العروض (خصم نسبة، خصم ثابت، كمية مجانية)
✅ أداء محسّن مع Eager Loading
✅ سهولة العرض في الموبايل

---

**الميزة جاهزة للاستخدام! 🎉**
