# دليل استخدام Postman Collection للطلبات (Orders API)

## Headers المطلوبة لجميع الطلبات

يجب إضافة هذه Headers لجميع الطلبات:

```
Accept: application/json
Authorization: Bearer {{auth_token}}
Content-Type: application/json
```

## شرح الحقول المهمة

### 1. notes_customer و notes_company
- **notes_customer**: ملاحظات العميل (يستخدمها العميل عند الإنشاء والتحديث)
- **notes_company**: ملاحظات الشركة (تستخدمها الشركة عند التحديث)
- كلاهما اختياري (nullable)

### 2. order_item_index
- يشير إلى **رقم المنتج** في قائمة `order_items` (يبدأ من 0)
- مثال: إذا كان لديك 3 منتجات:
  - المنتج الأول: `order_item_index = 0`
  - المنتج الثاني: `order_item_index = 1`
  - المنتج الثالث: `order_item_index = 2`
- يُستخدم لربط البونص بالمنتج الصحيح

### 3. الحسابات المطلوبة
```
final_line_total_snapshot = (qty × unit_price_snapshot) - discount_amount_snapshot
```

## البيانات الكاملة لإنشاء طلب (Store Order)

### 1. طلب بدون عروض (Order Without Offers)
```json
{
    "company_id": 35,
    "notes_customer": "طلب بدون عروض - اختبار",
    "order_items": [
        {
            "product_id": 53,
            "qty": 100,
            "unit_price_snapshot": 200.00,
            "discount_amount_snapshot": 0.00,
            "final_line_total_snapshot": 20000.00,
            "selected_offer_id": null
        }
    ],
    "order_item_bonuses": []
}
```

### 2. طلب مع خصم نسبة مئوية (Percentage Discount - 20%)
```json
{
    "company_id": 35,
    "notes_customer": "طلب مع خصم نسبة مئوية 20%",
    "order_items": [
        {
            "product_id": 53,
            "qty": 1000,
            "unit_price_snapshot": 200.00,
            "discount_amount_snapshot": 40000.00,
            "final_line_total_snapshot": 160000.00,
            "selected_offer_id": 7
        }
    ],
    "order_item_bonuses": []
}
```
**الحساب:** 1000 × 200 = 200,000 ريال، خصم 20% = 40,000 ريال، النهائي = 160,000 ريال

### 3. طلب مع خصم ثابت (Fixed Discount - 1000 ريال)
```json
{
    "company_id": 35,
    "notes_customer": "طلب مع خصم ثابت 1000 ريال",
    "order_items": [
        {
            "product_id": 69,
            "qty": 1000,
            "unit_price_snapshot": 50.00,
            "discount_amount_snapshot": 1000.00,
            "final_line_total_snapshot": 49000.00,
            "selected_offer_id": 8
        }
    ],
    "order_item_bonuses": []
}
```
**الحساب:** 1000 × 50 = 50,000 ريال، خصم 1,000 ريال = 49,000 ريال

### 4. طلب مع بونص (Bonus Quantity - 12 قطعة)
```json
{
    "company_id": 35,
    "notes_customer": "طلب مع بونص 12 قطعة مجانية",
    "order_items": [
        {
            "product_id": 53,
            "qty": 1000,
            "unit_price_snapshot": 200.00,
            "discount_amount_snapshot": 0.00,
            "final_line_total_snapshot": 200000.00,
            "selected_offer_id": 14
        }
    ],
    "order_item_bonuses": [
        {
            "order_item_index": 0,
            "bonus_product_id": 53,
            "bonus_qty": 12
        }
    ]
}
```
**ملاحظة:** `order_item_index: 0` يشير إلى المنتج الأول في `order_items`

### 5. طلب متعدد المنتجات (Multiple Items with Mixed Offers)
```json
{
    "company_id": 35,
    "notes_customer": "طلب متعدد المنتجات مع عروض مختلفة",
    "order_items": [
        {
            "product_id": 53,
            "qty": 1000,
            "unit_price_snapshot": 200.00,
            "discount_amount_snapshot": 40000.00,
            "final_line_total_snapshot": 160000.00,
            "selected_offer_id": 7
        },
        {
            "product_id": 66,
            "qty": 500,
            "unit_price_snapshot": 50.00,
            "discount_amount_snapshot": 0.00,
            "final_line_total_snapshot": 25000.00,
            "selected_offer_id": 14
        }
    ],
    "order_item_bonuses": [
        {
            "order_item_index": 1,
            "bonus_product_id": 66,
            "bonus_qty": 10
        }
    ]
}
```
**ملاحظة:** `order_item_index: 1` يشير إلى المنتج **الثاني** (product_id: 66)

### 6. طلب كبير - 3 منتجات مع بونصات متعددة
```json
{
    "company_id": 35,
    "notes_customer": "طلب كبير مع 3 منتجات وبونصات",
    "order_items": [
        {
            "product_id": 53,
            "qty": 2000,
            "unit_price_snapshot": 200.00,
            "discount_amount_snapshot": 80000.00,
            "final_line_total_snapshot": 320000.00,
            "selected_offer_id": 7
        },
        {
            "product_id": 66,
            "qty": 1000,
            "unit_price_snapshot": 50.00,
            "discount_amount_snapshot": 0.00,
            "final_line_total_snapshot": 50000.00,
            "selected_offer_id": 14
        },
        {
            "product_id": 69,
            "qty": 500,
            "unit_price_snapshot": 50.00,
            "discount_amount_snapshot": 0.00,
            "final_line_total_snapshot": 25000.00,
            "selected_offer_id": null
        }
    ],
    "order_item_bonuses": [
        {
            "order_item_index": 1,
            "bonus_product_id": 66,
            "bonus_qty": 20
        }
    ]
}
```
**شرح order_item_index:**
- المنتج الأول (index 0): product_id 53 - بدون بونص
- المنتج الثاني (index 1): product_id 66 - **له بونص 20 قطعة**
- المنتج الثالث (index 2): product_id 69 - بدون بونص

## Endpoints المتاحة

### 1. Authentication
- **POST** `/api/login` - تسجيل الدخول

### 2. Orders CRUD
- **GET** `/api/orders` - قائمة الطلبات (مع فلترة و pagination)
- **GET** `/api/orders/{id}` - تفاصيل طلب محدد
- **POST** `/api/orders` - إنشاء طلب جديد
- **PUT** `/api/orders/{id}` - تحديث طلب
- **DELETE** `/api/orders/{id}` - حذف طلب

### 3. Filtering & Pagination
```
GET /api/orders?status=pending&per_page=10&page=1
GET /api/orders?search=ORD-123
GET /api/orders?company_user_id=35
```

## Update Order Examples

### تحديث حالة الطلب (Company Approves)
```json
{
    "status": "approved",
    "notes_company": "تمت الموافقة على الطلب"
}
```

### تحديث ملاحظات العميل
```json
{
    "notes_customer": "ملاحظات محدثة من العميل"
}
```

### إلغاء الطلب
```json
{
    "status": "cancelled",
    "notes_customer": "إلغاء الطلب"
}
```

## المتغيرات المستخدمة

- `{{base_url}}` - http://localhost:8000/api
- `{{auth_token}}` - يتم تعيينه تلقائياً بعد تسجيل الدخول
- `{{company_id}}` - 35
- `{{product_id_1}}` - 53
- `{{product_id_2}}` - 66
- `{{offer_id_percentage}}` - 7
- `{{offer_id_fixed}}` - 8
- `{{offer_id_bonus}}` - 14
- `{{order_id}}` - يتم تعيينه تلقائياً بعد إنشاء طلب

## ملاحظات مهمة

1. جميع الطلبات تتطلب Authentication (Bearer Token)
2. العميل (Customer) فقط يمكنه إنشاء الطلبات
3. الشركة (Company) يمكنها الموافقة على الطلبات وتحديث حالتها
4. العميل يمكنه حذف الطلبات في حالة `pending` فقط
5. استخدم `notes_customer` عند الإنشاء والتحديث (اسم العمود الفعلي في قاعدة البيانات)
6. استخدم `notes_company` للشركة عند التحديث
7. `order_item_index` يبدأ من 0 ويشير إلى ترتيب المنتج في `order_items`
