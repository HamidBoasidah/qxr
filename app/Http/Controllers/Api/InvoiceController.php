<?php

namespace App\Http\Controllers\Api;

use App\DTOs\InvoiceDTO;
use App\Http\Controllers\Controller;
use App\Http\Traits\CanFilter;
use App\Http\Traits\SuccessResponse;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    use SuccessResponse, CanFilter;

    public function __construct(
        private InvoiceRepository $invoices
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of invoices
     * العميل يرى فواتيره فقط، الشركة ترى فواتير الطلبات الموجهة لها
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Invoice::class);

        $perPage = (int) $request->get('per_page', 10);
        $user = $request->user();

        $query = $this->invoices->query($this->indexWith());

        // تصفية حسب نوع المستخدم عبر علاقة order
        $query->whereHas('order', function ($q) use ($user) {
            if ($user->user_type === 'customer') {
                $q->where('customer_user_id', $user->id);
            } elseif ($user->user_type === 'company') {
                $q->where('company_user_id', $user->id);
            }
        });

        // تطبيق الفلاتر
        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(fn ($invoice) => InvoiceDTO::fromModel($invoice)->toIndexArray());

        return $this->collectionResponse($paginated, 'تم جلب قائمة الفواتير بنجاح');
    }

    /**
     * Display the specified invoice
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $invoice = $this->invoices->findOrFail($id, $this->detailWith());

            $this->authorize('view', $invoice);

            return $this->resourceResponse(
                InvoiceDTO::fromModel($invoice)->toDetailArray(),
                'تم جلب بيانات الفاتورة بنجاح'
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'الفاتورة المطلوبة غير موجودة'
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بعرض هذه الفاتورة'
            ], 403);
        }
    }

    /**
     * العلاقات المطلوبة لقائمة الفواتير
     */
    protected function indexWith(): array
    {
        return [
            'order:id,order_no,company_user_id,customer_user_id',
            'order.company:id,first_name,last_name',
            'order.customer:id,first_name,last_name',
        ];
    }

    /**
     * العلاقات المطلوبة لعرض تفاصيل الفاتورة
     */
    protected function detailWith(): array
    {
        return [
            'order:id,order_no,status,submitted_at,approved_at,delivered_at,company_user_id,customer_user_id',
            'order.company:id,first_name,last_name',
            'order.customer:id,first_name,last_name',
            'items.product:id,name',
            'bonusItems.product:id,name',
        ];
    }

    /**
     * حقول البحث النصي
     */
    protected function getSearchableFields(): array
    {
        return ['invoice_no'];
    }

    /**
     * فلاتر المفاتيح الخارجية والقيم المنطقية
     */
    protected function getForeignKeyFilters(): array
    {
        return [
            'status' => 'status',
            'order_id' => 'order_id',
        ];
    }
}
