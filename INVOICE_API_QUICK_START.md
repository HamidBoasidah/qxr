# دليل البدء السريع - Invoice API

## نظرة عامة
تم إنشاء API للفواتير يسمح للعملاء والشركات بعرض فواتيرهم فقط.

## الملفات المنشأة ✅

| الملف | المسار | الوصف |
|-------|--------|-------|
| InvoicePolicy | `app/Policies/InvoicePolicy.php` | صلاحيات الوصول |
| InvoiceRepository | `app/Repositories/InvoiceRepository.php` | التعامل مع قاعدة البيانات |
| InvoiceController | `app/Http/Controllers/Api/InvoiceController.php` | API endpoints |
| Routes | `routes/api.php` | مسارات الـ API |
| Documentation | `API_INVOICES_DOCUMENTATION.md` | التوثيق الكامل |
| Postman Collection | `postman/Invoices_API.postman_collection.json` | مجموعة Postman |
| Test Script | `scripts/test_invoice_api.php` | سكريبت الاختبار |

## الاختبارات السريعة ✓

### 1. التحقق من Routes
```bash
php artisan route:list --path=api/invoices
```

**النتيجة:**
```
GET|HEAD  api/invoices .................... Api\InvoiceController@index
GET|HEAD  api/invoices/{id} ................ Api\InvoiceController@show
```

### 2. اختبار DTO والـ Policy
```bash
php scripts/test_invoice_api.php
```

**النتيجة:**
```
=== اختبار Invoice API ===

1. عدد الفواتير في قاعدة البيانات: 10

2. اختبار DTO: ✓

3. اختبار Policy للعميل:
   - يمكنه عرض قائمة الفواتير: نعم ✓
   - يمكنه عرض فاتورته: نعم ✓
   - لا يمكنه عرض فاتورة غيره: نعم ✓

4. اختبار Policy للشركة:
   - يمكنها عرض قائمة الفواتير: نعم ✓
   - يمكنها عرض فاتورتها: نعم ✓
   - لا يمكنها عرض فاتورة غيرها: نعم ✓

5. اختبار InvoiceRepository: ✓

6. اختبار Filtering: ✓

=== انتهى الاختبار ===
```

## API Endpoints

### 1. GET /api/invoices
عرض قائمة الفواتير للمستخدم الحالي.

**Parameters:**
- `per_page` (optional): عدد العناصر في الصفحة
- `page` (optional): رقم الصفحة
- `search` (optional): البحث في invoice_no
- `status` (optional): unpaid, paid, void
- `order_id` (optional): تصفية حسب رقم الطلب

**Example:**
```bash
curl -X GET "http://localhost:8000/api/invoices?per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 2. GET /api/invoices/{id}
عرض تفاصيل فاتورة محددة.

**Example:**
```bash
curl -X GET "http://localhost:8000/api/invoices/1" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## الأمان والصلاحيات 🔒

### العميل (Customer)
- ✅ يمكنه عرض قائمة فواتيره فقط
- ✅ يمكنه عرض تفاصيل فواتيره فقط
- ❌ لا يمكنه عرض فواتير عملاء آخرين

### الشركة (Company)
- ✅ يمكنها عرض قائمة فواتير الطلبات الموجهة لها فقط
- ✅ يمكنها عرض تفاصيل فواتيرها فقط
- ❌ لا يمكنها عرض فواتير شركات أخرى

### الـ Admin
- ❌ لا يمكنه الوصول عبر هذا API
- ℹ️ يستخدم Admin routes (`admin.invoices.index`, `admin.invoices.show`)

## استخدام Postman

1. افتح Postman
2. استورد Collection: `postman/Invoices_API.postman_collection.json`
3. عدل متغيرات الـ Environment:
   - `base_url`: http://localhost:8000
   - `access_token`: [احصل عليه من endpoint تسجيل الدخول]
4. جرب الـ Requests

## البنية التقنية

### DTO (Data Transfer Object)
```php
InvoiceDTO::fromModel($invoice)
    ->toIndexArray()  // للقوائم
    ->toDetailArray() // للتفاصيل
```

### Repository Pattern
```php
$this->invoices->query($with)
$this->invoices->findOrFail($id, $with)
$this->invoices->find($id, $with)
```

### Policy Authorization
```php
$this->authorize('viewAny', Invoice::class)
$this->authorize('view', $invoice)
```

### Filtering
- **whereHas**: لتصفية حسب المستخدم (customer/company)
- **applyFilters**: للبحث والفلترة (status, order_id, search)

## ملاحظات مهمة ⚠️

1. **Authentication Required**: جميع الـ endpoints تتطلب Bearer Token
2. **Eager Loading**: يتم استخدام `with()` لتجنب N+1 queries
3. **Pagination**: Default هو 10 عناصر لكل صفحة
4. **Soft Deletes**: إذا كان Order محذوف soft delete، لن تظهر فاتورته

## التوثيق الكامل

للمزيد من التفاصيل، راجع:
- `API_INVOICES_DOCUMENTATION.md` - التوثيق الكامل
- `postman/Invoices_API.postman_collection.json` - Postman Collection

## الدعم

إذا واجهت أي مشاكل:
1. تحقق من الـ logs: `tail -f storage/logs/laravel.log`
2. استخدم test script: `php scripts/test_invoice_api.php`
3. تحقق من Routes: `php artisan route:list --path=api/invoices`
