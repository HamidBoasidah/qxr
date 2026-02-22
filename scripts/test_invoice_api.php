<?php

/**
 * Script لاختبار Invoice API بشكل سريع
 * الاستخدام: php scripts/test_invoice_api.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Invoice;
use App\DTOs\InvoiceDTO;
use Illuminate\Support\Facades\Gate;

echo "=== اختبار Invoice API ===\n\n";

// 1. اختبار عدد الفواتير
$invoiceCount = Invoice::count();
echo "1. عدد الفواتير في قاعدة البيانات: {$invoiceCount}\n\n";

// 2. اختبار DTO
$invoice = Invoice::with(['order.company', 'order.customer', 'items.product', 'bonusItems.product'])->first();
if ($invoice) {
    echo "2. اختبار DTO:\n";
    echo "   - رقم الفاتورة: {$invoice->invoice_no}\n";
    echo "   - رقم الطلب: {$invoice->order->order_no}\n";
    
    $dto = InvoiceDTO::fromModel($invoice);
    echo "   - DTO Index Array:\n";
    echo "     " . json_encode($dto->toIndexArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}

// 3. اختبار Policy - Customer
$customer = User::where('user_type', 'customer')->first();
if ($customer && $invoice) {
    echo "3. اختبار Policy للعميل:\n";
    echo "   - اسم العميل: {$customer->first_name} {$customer->last_name}\n";
    
    // التحقق من viewAny
    $canViewAny = Gate::forUser($customer)->allows('viewAny', Invoice::class);
    echo "   - يمكنه عرض قائمة الفواتير: " . ($canViewAny ? 'نعم ✓' : 'لا ✗') . "\n";
    
    // التحقق من view لفاتورة تخصه
    $customerInvoice = Invoice::whereHas('order', function($q) use ($customer) {
        $q->where('customer_user_id', $customer->id);
    })->with('order')->first();
    
    if ($customerInvoice) {
        $canView = Gate::forUser($customer)->allows('view', $customerInvoice);
        echo "   - يمكنه عرض فاتورته: " . ($canView ? 'نعم ✓' : 'لا ✗') . "\n";
        
        // التحقق من view لفاتورة لا تخصه
        $otherInvoice = Invoice::whereHas('order', function($q) use ($customer) {
            $q->where('customer_user_id', '!=', $customer->id);
        })->with('order')->first();
        
        if ($otherInvoice) {
            $cannotView = Gate::forUser($customer)->denies('view', $otherInvoice);
            echo "   - لا يمكنه عرض فاتورة غيره: " . ($cannotView ? 'نعم ✓' : 'لا ✗') . "\n";
        }
    }
    echo "\n";
}

// 4. اختبار Policy - Company
$company = User::where('user_type', 'company')->first();
if ($company && $invoice) {
    echo "4. اختبار Policy للشركة:\n";
    echo "   - اسم الشركة: {$company->first_name} {$company->last_name}\n";
    
    // التحقق من viewAny
    $canViewAny = Gate::forUser($company)->allows('viewAny', Invoice::class);
    echo "   - يمكنها عرض قائمة الفواتير: " . ($canViewAny ? 'نعم ✓' : 'لا ✗') . "\n";
    
    // التحقق من view لفاتورة تخصها
    $companyInvoice = Invoice::whereHas('order', function($q) use ($company) {
        $q->where('company_user_id', $company->id);
    })->with('order')->first();
    
    if ($companyInvoice) {
        $canView = Gate::forUser($company)->allows('view', $companyInvoice);
        echo "   - يمكنها عرض فاتورتها: " . ($canView ? 'نعم ✓' : 'لا ✗') . "\n";
        
        // التحقق من view لفاتورة لا تخصها
        $otherInvoice = Invoice::whereHas('order', function($q) use ($company) {
            $q->where('company_user_id', '!=', $company->id);
        })->with('order')->first();
        
        if ($otherInvoice) {
            $cannotView = Gate::forUser($company)->denies('view', $otherInvoice);
            echo "   - لا يمكنها عرض فاتورة غيرها: " . ($cannotView ? 'نعم ✓' : 'لا ✗') . "\n";
        }
    }
    echo "\n";
}

// 5. اختبار Repository
echo "5. اختبار InvoiceRepository:\n";
$repo = new \App\Repositories\InvoiceRepository(new \App\Models\Invoice());
$query = $repo->query(['order.company', 'order.customer']);
$count = $query->count();
echo "   - عدد الفواتير من Repository: {$count}\n";

if ($invoice) {
    $found = $repo->find($invoice->id, ['order']);
    echo "   - البحث عن فاتورة بـ ID: " . ($found ? 'نجح ✓' : 'فشل ✗') . "\n";
}
echo "\n";

// 6. اختبار Filtering
echo "6. اختبار Filtering:\n";
if ($customer) {
    $customerInvoices = Invoice::whereHas('order', function($q) use ($customer) {
        $q->where('customer_user_id', $customer->id);
    })->count();
    echo "   - فواتير العميل: {$customerInvoices}\n";
}

if ($company) {
    $companyInvoices = Invoice::whereHas('order', function($q) use ($company) {
        $q->where('company_user_id', $company->id);
    })->count();
    echo "   - فواتير الشركة: {$companyInvoices}\n";
}

$unpaidInvoices = Invoice::where('status', 'unpaid')->count();
echo "   - الفواتير غير المدفوعة: {$unpaidInvoices}\n";

$paidInvoices = Invoice::where('status', 'paid')->count();
echo "   - الفواتير المدفوعة: {$paidInvoices}\n";

echo "\n=== انتهى الاختبار ===\n";
