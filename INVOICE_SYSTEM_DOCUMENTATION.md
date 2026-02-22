# نظام الفواتير التلقائي

## نظرة عامة

تم تنفيذ نظام فواتير احترافي يقوم تلقائيًا بإنشاء فاتورة عند موافقة الشركة على الطلب (تغيير الحالة إلى `approved`).

## المكونات المنفذة

### 1. InvoiceService
- **الموقع**: `app/Services/InvoiceService.php`
- **المهمة**: إدارة إنشاء الفواتير من الطلبات المعتمدة

#### الوظائف الرئيسية:

**`createInvoiceForOrder(Order $order): Invoice`**
- يتحقق من عدم وجود فاتورة مسبقة للطلب (منع التكرار)
- ينشئ الفاتورة مع رقم فريد (INV-YYYYMMDD-XXXXXX)
- ينسخ order_items إلى invoice_items مع:
  - product_id
  - qty
  - unit_price_snapshot
  - line_total_snapshot
  - description_snapshot (اسم المنتج)
- ينسخ order_item_bonuses إلى invoice_bonus_items
- يحسب:
  - subtotal_snapshot (مجموع line_subtotals)
  - discount_total_snapshot (مجموع الخصومات)
  - total_snapshot (subtotal - discount)
- يستخدم DB::transaction لضمان سلامة البيانات

### 2. تحديثات OrderService
- **الموقع**: `app/Services/OrderService.php`
- **التغييرات**:
  - إضافة InvoiceService إلى constructor
  - استدعاء `invoiceService->createInvoiceForOrder()` عند تغيير الحالة إلى `approved`

### 3. تحديثات Order Model
- **الموقع**: `app/Models/Order.php`
- **التغييرات**:
  - إضافة علاقة `invoice()` → hasOne(Invoice::class)

### 4. تحديثات Models
- **InvoiceItem** و **InvoiceBonusItem**:
  - تم تغييرها لترث من `Model` مباشرة بدلاً من `BaseModel` (الجداول لا تستخدم soft deletes)

## كيفية الاستخدام

### تلقائي
عند استخدام OrderService لتغيير حالة الطلب إلى `approved`:

```php
$orderService = app(OrderService::class);
$order = $orderService->updateStatusByCompany(
    orderId: $orderId,
    newStatus: 'approved',
    companyId: $companyId,
    note: 'تم قبول الطلب'
);
// سيتم إنشاء الفاتورة تلقائيًا
```

### يدوي
يمكن إنشاء فاتورة يدويًا لطلب معتمد:

```php
$invoiceService = app(InvoiceService::class);
$invoice = $invoiceService->createInvoiceForOrder($order);
```

## الحماية من التكرار

- يتحقق النظام من وجود فاتورة مسبقة عند كل محاولة إنشاء
- في حالة وجود فاتورة، يُرجع الفاتورة الموجودة دون إنشاء فاتورة جديدة
- يعمل داخل transaction لتجنب race conditions

## الاختبارات

### اختبار شامل
```bash
php scripts/test_invoice_creation.php
```

يقوم الاختبار بـ:
- إنشاء طلب تجريبي مع عدة منتجات
- اعتماد الطلب
- إنشاء الفاتورة
- التحقق من عدم إنشاء فاتورة مكررة
- عرض تفاصيل الفاتورة وعناصرها

### نتائج الاختبار
✅ إنشاء الفاتورة بنجاح
✅ حساب المجاميع بشكل صحيح
✅ نسخ order_items إلى invoice_items
✅ منع التكرار يعمل بشكل صحيح

## البنية

```
invoices (جدول)
├── id
├── invoice_no (فريد)
├── order_id (فريد، علاقة 1:1)
├── subtotal_snapshot
├── discount_total_snapshot
├── total_snapshot
├── issued_at
├── status (unpaid/paid/void)
├── deleted_at
└── timestamps

invoice_items (جدول)
├── id
├── invoice_id
├── product_id
├── description_snapshot
├── qty
├── unit_price_snapshot
├── line_total_snapshot
└── timestamps

invoice_bonus_items (جدول)
├── id
├── invoice_id
├── product_id
├── qty
├── note
└── timestamps
```

## الحسابات

```php
// لكل order_item في الطلب:
line_subtotal = qty × unit_price_snapshot
line_discount = discount_amount_snapshot
line_total = final_line_total_snapshot (المحفوظ من وقت إنشاء الطلب)

// في الفاتورة:
subtotal = Σ(line_subtotals)
discount_total = Σ(line_discounts)
total = subtotal - discount_total
```

## الخصائص

✅ Production-ready
✅ Transaction-safe
✅ Duplicate prevention
✅ Snapshot-based (لا تتأثر بتغيير الأسعار لاحقًا)
✅ Auto-generated unique invoice numbers
✅ Supports order items with bonuses
✅ Clean code بدون تعليقات زائدة
