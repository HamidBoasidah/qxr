# نظام عرض الفواتير - Admin & Company

## نظرة عامة

تم إنشاء نظام كامل لعرض الفواتير في لوحات تحكم الإدارة والشركات، متبعًا نفس نمط الطلبات والعروض في المشروع.

## الملفات المُنشأة

### Backend (Laravel)

#### DTOs
- **app/DTOs/InvoiceDTO.php** - تحويل بيانات الفواتير

#### Controllers
- **app/Http/Controllers/Admin/InvoiceController.php** - للإدارة
- **app/Http/Controllers/Company/InvoiceController.php** - للشركات

#### Routes
- **routes/admin.php** - إضافة `invoices` resource routes
- **routes/company.php** - إضافة `invoices` resource routes

### Frontend (Vue + Inertia)

#### Pages
- **resources/js/Pages/Admin/Invoice/Index.vue** - قائمة الفواتير (إدارة)
- **resources/js/Pages/Admin/Invoice/Show.vue** - عرض فاتورة (إدارة)
- **resources/js/Pages/Company/Invoice/Index.vue** - قائمة الفواتير (شركات)
- **resources/js/Pages/Company/Invoice/Show.vue** - عرض فاتورة (شركات)

#### Components
- **resources/js/components/admin/invoice/ShowInvoices.vue** - جدول الفواتير
- **resources/js/components/admin/invoice/ShowInvoice.vue** - تفاصيل فاتورة
- **resources/js/components/company/invoice/ShowInvoices.vue** - جدول الفواتير
- **resources/js/components/company/invoice/ShowInvoice.vue** - تفاصيل فاتورة

#### i18n Keys
- **resources/js/locales/ar.json** - إضافة `invoice` section و `menu.invoices`
- **resources/js/locales/en.json** - إضافة `invoice` section و `menu.invoices`

## الميزات المُنفذة

### للإدارة (Admin)

#### عرض القائمة (`/admin/invoices`)
- ✅ جدول بجميع الفواتير
- ✅ بحث في رقم الفاتورة، رقم الطلب، الشركة، العميل
- ✅ ترتيب حسب الأعمدة
- ✅ pagination
- ✅ عرض: رقم الفاتورة، رقم الطلب، الشركة، العميل، التاريخ، الإجمالي، الحالة
- ✅ زر عرض التفاصيل

#### عرض التفاصيل (`/admin/invoices/{id}`)
- ✅ معلومات الفاتورة (رقم، حالة، تاريخ الإصدار)
- ✅ معلومات الطلب المرتبط (رقم، حالة، تواريخ)
- ✅ الإجماليات (الإجمالي قبل الخصم، الخصم، الإجمالي النهائي)
- ✅ جدول عناصر الفاتورة (المنتج، الكمية، السعر، الإجمالي)
- ✅ جدول الهدايا (إن وجدت)

### للشركات (Company)

#### عرض القائمة (`/company/invoices`)
- ✅ جدول بفواتير الشركة فقط
- ✅ نفس ميزات البحث والترتيب والـ pagination
- ✅ عرض نفس الأعمدة
- ✅ زر عرض التفاصيل

#### عرض التفاصيل (`/company/invoices/{id}`)
- ✅ نفس تفاصيل Admin ولكن للفواتير التي تخص الشركة فقط
- ✅ التحقق من ملكية الفاتورة (authorization)

## الحالات المدعومة

### حالات الفاتورة
- **unpaid** (غير مدفوعة) - أصفر
- **paid** (مدفوعة) - أخضر
- **void** (ملغاة) - رمادي

## Routes

### Admin Routes
```php
GET  /admin/invoices         -> index (قائمة الفواتير)
GET  /admin/invoices/{id}    -> show  (عرض الفاتورة)
```

### Company Routes
```php
GET  /company/invoices       -> index (قائمة الفواتير)
GET  /company/invoices/{id}  -> show  (عرض الفاتورة)
```

## Authorization

### Admin
- يتطلب صلاحية `invoices.view` (محددة في middleware)

### Company
- يتطلب تسجيل دخول فقط
- يُفلتَر تلقائيًا لعرض فواتير الشركة المُسجلة الدخول فقط

## البيانات المُعرضة

### في القائمة (Index)
```javascript
{
  id,
  invoice_no,
  order_id,
  order_no,
  company_name,
  customer_name,
  total_snapshot,
  issued_at,
  status
}
```

### في التفاصيل (Show)
```javascript
{
  id,
  invoice_no,
  order_id,
  order_no,
  company_name,
  customer_name,
  subtotal_snapshot,
  discount_total_snapshot,
  total_snapshot,
  issued_at,
  status,
  created_at,
  items: [
    {
      id,
      product_id,
      product_name,
      description_snapshot,
      qty,
      unit_price_snapshot,
      line_total_snapshot
    }
  ],
  bonus_items: [
    {
      id,
      product_id,
      product_name,
      qty,
      note
    }
  ],
  order: {
    id,
    order_no,
    status,
    submitted_at,
    approved_at,
    delivered_at
  }
}
```

## UI Components المستخدمة

- **Badge** - لعرض الحالات
- **InfoItem** - لعرض المعلومات
- **SortArrows** - للترتيب
- **ComponentCard** - للـ containers
- **PageBreadcrumb** - للـ navigation

## التكامل مع النظام الحالي

✅ متوافق مع نمط الـ Orders و Offers
✅ يستخدم نفس الـ layouts (AdminLayout, CompanyLayout)
✅ يستخدم نفس الـ components المشتركة
✅ يستخدم نفس نظام الـ i18n
✅ يستخدم نفس نمط الـ routing
✅ يستخدم نفس نمط الـ DTOs

## كيفية الوصول

### من لوحة الإدارة
```
Dashboard → Menu → Invoices (الفواتير)
```

### من لوحة الشركات
```
Dashboard → Menu → Invoices (الفواتير)
```

## الخطوات التالية (اختياري)

1. إضافة تصدير PDF للفواتير
2. إضافة إرسال الفاتورة عبر البريد الإلكتروني
3. إضافة تحديث حالة الفاتورة (paid/void)
4. إضافة فلاتر إضافية (حسب التاريخ، الحالة، إلخ)
5. إضافة إحصائيات الفواتير في Dashboard

## Notes

- جميع التواريخ معروضة بصيغة Y-m-d H:i
- الأسعار معروضة بصيغة رقمية عربية مع منزلتين عشريتين
- البحث case-insensitive ويبحث في كل الأعمدة المهمة
- الترتيب يعمل على جميع الأعمدة
- Pagination يدعم 5، 8، 10 عناصر لكل صفحة
