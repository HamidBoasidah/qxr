# تصميم: توحيد معالجة أخطاء الصلاحيات في API

## 1. نظرة عامة على التصميم

### 1.1 الهدف
تحديث `ProductController` لمعالجة أخطاء الصلاحيات بنفس الطريقة المستخدمة في `AddressController`، مما يضمن استجابات API منسقة وآمنة.

### 1.2 النهج
- استخدام `try-catch` لمعالجة `AuthorizationException`
- تحويل أخطاء الصلاحيات إلى `NotFoundException` لأسباب أمنية
- الحفاظ على نفس هيكل الكود الحالي مع إضافة معالجة الاستثناءات فقط

## 2. التغييرات المطلوبة

### 2.1 استيراد الاستثناءات المطلوبة

يجب إضافة استيراد `AuthorizationException` في بداية `ProductController`:

```php
use Illuminate\Auth\Access\AuthorizationException;
```

### 2.2 تحديث method: update()

**الكود الحالي:**
```php
public function update(UpdateProductRequest $request, ProductService $service, ProductRepository $products, $id)
{
    try {
        $product = $products->findOrFail($id, $this->baseWith());
        // authorize via policy (ownership + company type)
        $this->authorize('update', $product);

        $product = $service->update(
            $product->id,
            $request->validatedPayload(),
            $request->tagIdsOrNull(),
            $request->imagesPayloadOrNull(),
            $request->deleteImageIdsOrNull()
        )->load($this->baseWith());

        return $this->updatedResponse(
            ProductDTO::fromModel($product)->toArray(),
            'تم تحديث المنتج بنجاح'
        );
    } catch (AppValidationException $e) {
        return $e->render($request);
    } catch (ModelNotFoundException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    }

    $this->throwForbiddenException('تم تعطيل عملية التعديل');
}
```

**الكود المحدث:**
```php
public function update(UpdateProductRequest $request, ProductService $service, ProductRepository $products, $id)
{
    try {
        $product = $products->findOrFail($id, $this->baseWith());
        
        // authorize via policy (ownership + company type)
        $this->authorize('update', $product);

        $product = $service->update(
            $product->id,
            $request->validatedPayload(),
            $request->tagIdsOrNull(),
            $request->imagesPayloadOrNull(),
            $request->deleteImageIdsOrNull()
        )->load($this->baseWith());

        return $this->updatedResponse(
            ProductDTO::fromModel($product)->toArray(),
            'تم تحديث المنتج بنجاح'
        );
    } catch (AppValidationException $e) {
        return $e->render($request);
    } catch (ModelNotFoundException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    } catch (AuthorizationException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    }
}
```

### 2.3 تحديث method: destroy()

**الكود الحالي:**
```php
public function destroy(ProductService $service, ProductRepository $products, $id)
{
    try {
        $product = $products->findOrFail($id);
        // authorize via policy
        $this->authorize('delete', $product);

        $service->delete($product->id);
        return $this->deletedResponse('تم حذف المنتج بنجاح');
    } catch (ModelNotFoundException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    }

    $this->throwForbiddenException('تم تعطيل عملية الحذف');
}
```

**الكود المحدث:**
```php
public function destroy(ProductService $service, ProductRepository $products, $id)
{
    try {
        $product = $products->findOrFail($id);
        
        // authorize via policy
        $this->authorize('delete', $product);

        $service->delete($product->id);
        return $this->deletedResponse('تم حذف المنتج بنجاح');
    } catch (ModelNotFoundException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    } catch (AuthorizationException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    }
}
```

### 2.4 تحديث method: activate()

**الكود الحالي:**
```php
public function activate(ProductService $service, ProductRepository $products, $id)
{
    try {
        $product = $products->findOrFail($id, $this->baseWith());
        // authorize via policy
        $this->authorize('activate', $product);

        $product = $service->activate($product->id)->load($this->baseWith());
        return $this->activatedResponse(
            ProductDTO::fromModel($product)->toArray(),
            'تم تفعيل المنتج بنجاح'
        );
    } catch (ModelNotFoundException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    }

    // تم تعطيل تفعيل المنتجات مؤقتًا
    $this->throwForbiddenException('تم تعطيل عملية التفعيل');
}
```

**الكود المحدث:**
```php
public function activate(ProductService $service, ProductRepository $products, $id)
{
    try {
        $product = $products->findOrFail($id, $this->baseWith());
        
        // authorize via policy
        $this->authorize('activate', $product);

        $product = $service->activate($product->id)->load($this->baseWith());
        return $this->activatedResponse(
            ProductDTO::fromModel($product)->toArray(),
            'تم تفعيل المنتج بنجاح'
        );
    } catch (ModelNotFoundException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    } catch (AuthorizationException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    }
}
```

### 2.5 تحديث method: deactivate()

**الكود الحالي:**
```php
public function deactivate(ProductService $service, ProductRepository $products, $id)
{
    try {
        $product = $products->findOrFail($id, $this->baseWith());
        // authorize via policy
        $this->authorize('deactivate', $product);

        $product = $service->deactivate($product->id)->load($this->baseWith());
        return $this->deactivatedResponse(
            ProductDTO::fromModel($product)->toArray(),
            'تم تعطيل المنتج بنجاح'
        );
    } catch (ModelNotFoundException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    }

    // تم تعطيل إلغاء تفعيل المنتجات مؤقتًا
    $this->throwForbiddenException('تم تعطيل عملية إلغاء التفعيل');
}
```

**الكود المحدث:**
```php
public function deactivate(ProductService $service, ProductRepository $products, $id)
{
    try {
        $product = $products->findOrFail($id, $this->baseWith());
        
        // authorize via policy
        $this->authorize('deactivate', $product);

        $product = $service->deactivate($product->id)->load($this->baseWith());
        return $this->deactivatedResponse(
            ProductDTO::fromModel($product)->toArray(),
            'تم تعطيل المنتج بنجاح'
        );
    } catch (ModelNotFoundException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    } catch (AuthorizationException) {
        $this->throwNotFoundException('المنتج المطلوب غير موجود');
    }
}
```

### 2.6 إزالة الكود الميت (Dead Code)

في جميع الـ methods المذكورة أعلاه، يوجد كود بعد block الـ `try-catch` لن يتم الوصول إليه أبداً:

```php
$this->throwForbiddenException('...');
```

يجب إزالة هذه الأسطر لأنها unreachable code.

## 3. الاعتبارات الأمنية

### 3.1 عدم الكشف عن المعلومات
- عند فشل الصلاحية، نرجع نفس الرسالة "المنتج المطلوب غير موجود"
- هذا يمنع المهاجمين من معرفة ما إذا كان المنتج موجوداً أم لا
- يتبع نفس النهج المستخدم في `AddressController`

### 3.2 ترتيب المعالجة
1. أولاً: التحقق من وجود المورد (`findOrFail`)
2. ثانياً: التحقق من الصلاحية (`authorize`)
3. إذا فشل أي منهما: نرجع "غير موجود"

## 4. التأثير على الأنظمة الأخرى

### 4.1 لا يوجد تأثير على
- `Handler.php` - لا تغيير
- Policies - لا تغيير
- Controllers أخرى - لا تغيير
- Frontend/Mobile apps - سيحصلون على استجابات أفضل

### 4.2 التحسينات
- استجابات API أكثر احترافية
- عدم تسريب معلومات حساسة
- اتساق في معالجة الأخطاء

## 5. خطة التنفيذ

### 5.1 الخطوات
1. إضافة `use Illuminate\Auth\Access\AuthorizationException;`
2. تحديث `update()` method
3. تحديث `destroy()` method
4. تحديث `activate()` method
5. تحديث `deactivate()` method
6. إزالة الكود الميت من جميع الـ methods
7. إزالة `use Illuminate\Support\Facades\Auth;` غير المستخدم

### 5.2 الاختبار
- اختبار كل method مع منتج غير مملوك للمستخدم
- التحقق من الاستجابة المنسقة
- التحقق من عدم وجود stack trace

## 6. الخلاصة

التغييرات بسيطة ومباشرة:
- إضافة `catch (AuthorizationException)` لكل method محمي
- تحويل أخطاء الصلاحيات إلى `NotFoundException`
- إزالة الكود الميت
- النتيجة: استجابات API منسقة وآمنة ومتسقة
