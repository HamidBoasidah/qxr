# Invoice API Documentation

## نظرة عامة
تم إنشاء API للفواتير يسمح للعملاء والشركات بعرض الفواتير الخاصة بهم فقط.

## الملفات المنشأة

### 1. InvoicePolicy
**المسار:** `app/Policies/InvoicePolicy.php`

يتحكم في صلاحيات الوصول للفواتير:
- **viewAny**: يسمح للعملاء والشركات بعرض قائمة الفواتير
- **view**: يتحقق من أن الفاتورة تخص المستخدم المسجل (عبر Order)

### 2. InvoiceRepository
**المسار:** `app/Repositories/InvoiceRepository.php`

يوفر طرق للوصول إلى الفواتير من قاعدة البيانات:
- `query(array $with = [])`: إنشاء استعلام مع العلاقات
- `findOrFail(int $id, array $with = [])`: البحث عن فاتورة أو فشل
- `find(int $id, array $with = [])`: البحث عن فاتورة

### 3. InvoiceController
**المسار:** `app/Http/Controllers/Api/InvoiceController.php`

يوفر endpoints للفواتير:
- **index**: عرض قائمة الفواتير (مع pagination و filters)
- **show**: عرض تفاصيل فاتورة محددة

### 4. Routes
**المسار:** `routes/api.php`

تم إضافة routes للفواتير:
```php
Route::get('invoices', [App\Http\Controllers\Api\InvoiceController::class, 'index']);
Route::get('invoices/{id}', [App\Http\Controllers\Api\InvoiceController::class, 'show']);
```

### 5. AuthServiceProvider
**المسار:** `app/Providers/AuthServiceProvider.php`

تم تسجيل InvoicePolicy في $policies array.

---

## API Endpoints

### 1. عرض قائمة الفواتير
**Endpoint:** `GET /api/invoices`

**Authentication:** Required (Bearer Token)

**الوصف:** 
- العميل يرى فواتيره فقط
- الشركة ترى فواتير الطلبات الموجهة لها فقط

**Query Parameters:**
- `per_page` (optional): عدد العناصر في الصفحة (default: 10)
- `page` (optional): رقم الصفحة
- `search` (optional): البحث في invoice_no
- `status` (optional): تصفية حسب الحالة (unpaid, paid, void)
- `order_id` (optional): تصفية حسب رقم الطلب

**Response Example:**
```json
{
  "success": true,
  "message": "تم جلب قائمة الفواتير بنجاح",
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "invoice_no": "INV-20240001",
        "order_id": 123,
        "order_no": "ORD-20240123",
        "company_name": "أحمد محمد",
        "customer_name": "علي حسن",
        "total_snapshot": 15000.00,
        "issued_at": "2024-02-22 10:30",
        "status": "paid"
      }
    ],
    "per_page": 10,
    "total": 25
  }
}
```

**Status Codes:**
- `200 OK`: نجح الطلب
- `401 Unauthorized`: غير مصرح (Token غير صحيح)
- `403 Forbidden`: غير مسموح (المستخدم ليس عميل أو شركة)

---

### 2. عرض تفاصيل فاتورة
**Endpoint:** `GET /api/invoices/{id}`

**Authentication:** Required (Bearer Token)

**الوصف:**
- يعرض تفاصيل الفاتورة مع العناصر والهدايا
- يتحقق من أن الفاتورة تخص المستخدم المسجل

**Response Example:**
```json
{
  "success": true,
  "message": "تم جلب بيانات الفاتورة بنجاح",
  "data": {
    "id": 1,
    "invoice_no": "INV-20240001",
    "order_id": 123,
    "order_no": "ORD-20240123",
    "company_name": "أحمد محمد",
    "customer_name": "علي حسن",
    "subtotal_snapshot": 16000.00,
    "discount_total_snapshot": 1000.00,
    "total_snapshot": 15000.00,
    "issued_at": "2024-02-22 10:30",
    "status": "paid",
    "created_at": "2024-02-22 10:30",
    "items": [
      {
        "id": 1,
        "product_id": 45,
        "product_name": "منتج أ",
        "description_snapshot": "وصف المنتج",
        "qty": 10,
        "unit_price_snapshot": 1500.00,
        "line_total_snapshot": 15000.00
      }
    ],
    "bonus_items": [
      {
        "id": 1,
        "product_id": 46,
        "product_name": "هدية مجانية",
        "qty": 2,
        "note": "هدية مع الطلب"
      }
    ],
    "order": {
      "id": 123,
      "order_no": "ORD-20240123",
      "status": "delivered",
      "submitted_at": "2024-02-20 09:00",
      "approved_at": "2024-02-20 10:15",
      "delivered_at": "2024-02-22 08:00"
    }
  }
}
```

**Status Codes:**
- `200 OK`: نجح الطلب
- `401 Unauthorized`: غير مصرح (Token غير صحيح)
- `403 Forbidden`: غير مسموح (الفاتورة لا تخص المستخدم)
- `404 Not Found`: الفاتورة غير موجودة

---

## أمثلة الاستخدام

### 1. عرض جميع الفواتير للمستخدم الحالي
```bash
curl -X GET "http://localhost:8000/api/invoices" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### 2. عرض الفواتير مع pagination
```bash
curl -X GET "http://localhost:8000/api/invoices?per_page=20&page=2" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### 3. البحث في الفواتير
```bash
curl -X GET "http://localhost:8000/api/invoices?search=INV-2024" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### 4. تصفية حسب الحالة
```bash
curl -X GET "http://localhost:8000/api/invoices?status=unpaid" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

### 5. عرض تفاصيل فاتورة محددة
```bash
curl -X GET "http://localhost:8000/api/invoices/1" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

---

## الأمان والصلاحيات

### 1. التحقق من المصادقة
- جميع endpoints تتطلب Bearer Token
- يتم التحقق من صلاحية Token عبر Sanctum middleware

### 2. التحقق من الصلاحيات
- **viewAny**: يتحقق من أن المستخدم عميل أو شركة
- **view**: يتحقق من أن الفاتورة تخص المستخدم (عبر order->customer_user_id أو order->company_user_id)

### 3. تصفية البيانات
- العملاء يرون فواتيرهم فقط (order->customer_user_id)
- الشركات ترى فواتير طلباتها فقط (order->company_user_id)
- Admin لا يمكنه الوصول عبر هذا API (يستخدم Admin routes)

---

## DTO المستخدم

### InvoiceDTO
**المسار:** `app/DTOs/InvoiceDTO.php`

**Methods:**
- `fromModel(Invoice $invoice)`: تحويل Model إلى DTO
- `toIndexArray()`: إرجاع البيانات للقائمة (index)
- `toDetailArray()`: إرجاع البيانات التفصيلية (show)

**Fields في toIndexArray():**
- id, invoice_no, order_id, order_no
- company_name, customer_name
- total_snapshot, issued_at, status

**Fields في toDetailArray():**
- جميع fields من toIndexArray()
- subtotal_snapshot, discount_total_snapshot, created_at
- items[] (عناصر الفاتورة)
- bonus_items[] (الهدايا)
- order{} (بيانات الطلب المرتبط)

---

## الاختبار

### 1. التحقق من Routes
```bash
php artisan route:list --path=api/invoices
```

**النتيجة المتوقعة:**
```
GET|HEAD  api/invoices .................... Api\InvoiceController@index
GET|HEAD  api/invoices/{id} ................ Api\InvoiceController@show
```

### 2. اختبار مع Postman
1. قم بتسجيل الدخول والحصول على Token
2. استخدم Collection `Invoices_API.postman_collection.json` (يمكن إنشاؤه)
3. جرب endpoints المختلفة

### 3. اختبار مع Tinker
```bash
php artisan tinker
```

```php
// جلب مستخدم (عميل أو شركة)
$user = User::where('user_type', 'customer')->first();

// جلب الفواتير الخاصة به
$invoices = Invoice::whereHas('order', function($q) use ($user) {
    $q->where('customer_user_id', $user->id);
})->get();

// تحويل إلى DTO
$dto = \App\DTOs\InvoiceDTO::fromModel($invoices->first());
$dto->toIndexArray();
```

---

## ملاحظات مهمة

1. **العلاقات المحملة:**
   - في `index`: يتم تحميل (order, order.company, order.customer)
   - في `show`: يتم تحميل العلاقات السابقة + (items.product, bonusItems.product)

2. **Pagination:**
   - Default: 10 عناصر لكل صفحة
   - يمكن تغييره عبر `per_page` parameter

3. **Filters:**
   - البحث: في `invoice_no` فقط
   - Foreign keys: `status`, `order_id`

4. **Performance:**
   - يستخدم Eager Loading لتجنب N+1 queries
   - يستخدم Repository pattern للمرونة

5. **Future Enhancements:**
   - إضافة PDF export endpoint
   - إضافة email sending endpoint
   - إضافة statistics endpoint
   - إضافة payment tracking

---

## الدعم والاستفسارات

إذا واجهت أي مشاكل:
1. تحقق من الـ logs: `storage/logs/laravel.log`
2. تحقق من الـ routes: `php artisan route:list`
3. تحقق من الـ policies: `php artisan policy:show`
4. استخدم Tinker للاختبار: `php artisan tinker`
