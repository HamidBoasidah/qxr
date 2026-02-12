# مهام التنفيذ: توحيد معالجة أخطاء الصلاحيات في API

## 1. تحديث ProductController

### 1.1 إضافة استيراد AuthorizationException
- [x] إضافة `use Illuminate\Auth\Access\AuthorizationException;` في بداية الملف
- [x] إزالة `use Illuminate\Support\Facades\Auth;` غير المستخدم

### 1.2 تحديث method: update()
- [x] إضافة `catch (AuthorizationException)` block
- [x] معالجة الاستثناء برمي `NotFoundException`
- [x] إزالة السطر الأخير `$this->throwForbiddenException('تم تعطيل عملية التعديل');`

### 1.3 تحديث method: destroy()
- [x] إضافة `catch (AuthorizationException)` block
- [x] معالجة الاستثناء برمي `NotFoundException`
- [x] إزالة السطر الأخير `$this->throwForbiddenException('تم تعطيل عملية الحذف');`

### 1.4 تحديث method: activate()
- [x] إضافة `catch (AuthorizationException)` block
- [x] معالجة الاستثناء برمي `NotFoundException`
- [x] إزالة السطر الأخير `$this->throwForbiddenException('تم تعطيل عملية التفعيل');`

### 1.5 تحديث method: deactivate()
- [x] إضافة `catch (AuthorizationException)` block
- [x] معالجة الاستثناء برمي `NotFoundException`
- [x] إزالة السطر الأخير `$this->throwForbiddenException('تم تعطيل عملية إلغاء التفعيل');`

## 2. الاختبار والتحقق

### 2.1 اختبار يدوي
- [ ] اختبار تعديل منتج غير مملوك → يجب إرجاع 404 منسق
- [ ] اختبار حذف منتج غير مملوك → يجب إرجاع 404 منسق
- [ ] اختبار تفعيل منتج غير مملوك → يجب إرجاع 404 منسق
- [ ] اختبار تعطيل منتج غير مملوك → يجب إرجاع 404 منسق

### 2.2 التحقق من الجودة
- [x] التأكد من عدم وجود stack trace في أي استجابة
- [x] التأكد من تطابق هيكل الاستجابة مع AddressController
- [x] التحقق من عدم وجود أخطاء syntax
- [x] التحقق من عدم وجود unreachable code

## 3. المراجعة النهائية

### 3.1 مراجعة الكود
- [x] مراجعة جميع التغييرات
- [x] التأكد من الاتساق مع AddressController
- [x] التأكد من عدم التأثير على methods أخرى

### 3.2 التوثيق
- [x] تحديث التعليقات إذا لزم الأمر
- [x] التأكد من وضوح الكود
