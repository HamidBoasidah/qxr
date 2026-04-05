<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReturnInvoice\ReturnInvoiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnInvoiceRequest;
use App\Http\Traits\SuccessResponse;
use App\Models\Invoice;
use App\Models\ReturnInvoice;
use App\Services\ReturnInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnInvoiceController extends Controller
{
    use SuccessResponse;

    public function __construct(
        private ReturnInvoiceService $service
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all return invoices for the authenticated user.
     * - Customer: sees return invoices for their own invoices
     * - Company: sees return invoices belonging to their company
     *
     * Validates: Requirements 7.2, 7.4
     */
    public function index(Request $request): JsonResponse
    {
        $user    = $request->user();
        $perPage = (int) $request->query('per_page', 15);

        if ($user->user_type === 'customer') {
            // العميل يرى فواتير الاسترجاع الخاصة بفواتيره
            $returnInvoices = ReturnInvoice::with(['originalInvoice', 'returnPolicy'])
                ->whereHas('originalInvoice.order', fn($q) => $q->where('customer_user_id', $user->id))
                ->orderByDesc('created_at')
                ->paginate($perPage);
        } else {
            // الشركة ترى فواتير الاسترجاع الخاصة بها
            $returnInvoices = $this->service->listForCompany($user->id, $perPage);
        }

        return $this->collectionResponse($returnInvoices, 'تم جلب فواتير الاسترجاع بنجاح');
    }

    /**
     * Create a new return invoice — initiated by the customer who owns the original invoice.
     *
     * Validates: Requirements 3.2, 5.1, 5.4
     */
    public function store(StoreReturnInvoiceRequest $request): JsonResponse
    {
        $payload    = $request->validatedPayload();
        $customerId = $request->user()->id;

        /** @var Invoice $invoice */
        $invoice = Invoice::with('order')->findOrFail($payload['original_invoice_id']);

        // التحقق من أن العميل هو صاحب الفاتورة الأصلية
        if ($invoice->order->customer_user_id !== $customerId) {
            return response()->json([
                'success'    => false,
                'message'    => 'ليس لديك صلاحية إنشاء فاتورة استرجاع لهذه الفاتورة',
                'error_code' => 'unauthorized',
            ], 403);
        }

        // Resolve the return policy from the invoice (NOT NULL, always present)
        $policy = $invoice->returnPolicy;

        if ($policy === null) {
            return response()->json([
                'success'    => false,
                'message'    => 'No active return policy found for this company',
                'error_code' => 'no_policy_found',
            ], 422);
        }

        try {
            $this->service->validate($invoice, $payload['items'], $policy);
            $returnInvoice = $this->service->create($invoice, $payload['items'], $policy);
        } catch (ReturnInvoiceException $e) {
            return response()->json([
                'success'    => false,
                'message'    => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ], $e->getHttpStatus());
        }

        $returnInvoice->load('items');

        return $this->createdResponse($returnInvoice, 'تم إنشاء فاتورة الاسترجاع بنجاح');
    }

    /**
     * Show a specific return invoice with all its items.
     * - Customer: can view if they own the original invoice
     * - Company: can view if it belongs to their company
     *
     * Validates: Requirements 7.1, 7.3, 7.4
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $query = ReturnInvoice::with(['items', 'originalInvoice', 'returnPolicy']);

        if ($user->user_type === 'customer') {
            $returnInvoice = $query
                ->whereHas('originalInvoice.order', fn($q) => $q->where('customer_user_id', $user->id))
                ->find($id);
        } else {
            $returnInvoice = $query->where('company_id', $user->id)->find($id);
        }

        if ($returnInvoice === null) {
            return response()->json([
                'success' => false,
                'message' => 'فاتورة الاسترجاع غير موجودة أو لا تملك صلاحية الوصول إليها',
            ], 404);
        }

        return $this->resourceResponse($returnInvoice, 'تم جلب فاتورة الاسترجاع بنجاح');
    }

    /**
     * Approve a pending return invoice — company only.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->id;

        $returnInvoice = ReturnInvoice::where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if ($returnInvoice === null) {
            return response()->json([
                'success' => false,
                'message' => 'فاتورة الاسترجاع غير موجودة أو لا تنتمي لشركتك',
            ], 404);
        }

        if ($returnInvoice->status !== 'pending') {
            return response()->json([
                'success'    => false,
                'message'    => 'Invalid status transition: only pending invoices can be approved',
                'error_code' => 'invalid_status_transition',
            ], 422);
        }

        $returnInvoice->update(['status' => 'approved']);

        return $this->updatedResponse($returnInvoice, 'تم اعتماد فاتورة الاسترجاع بنجاح');
    }

    /**
     * Reject a pending return invoice — company only.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->id;

        $returnInvoice = ReturnInvoice::where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if ($returnInvoice === null) {
            return response()->json([
                'success' => false,
                'message' => 'فاتورة الاسترجاع غير موجودة أو لا تنتمي لشركتك',
            ], 404);
        }

        if ($returnInvoice->status !== 'pending') {
            return response()->json([
                'success'    => false,
                'message'    => 'Invalid status transition: only pending invoices can be rejected',
                'error_code' => 'invalid_status_transition',
            ], 422);
        }

        $returnInvoice->update(['status' => 'rejected']);

        return $this->updatedResponse($returnInvoice, 'تم رفض فاتورة الاسترجاع بنجاح');
    }
}
