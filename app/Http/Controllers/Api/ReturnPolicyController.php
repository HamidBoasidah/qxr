<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ReturnPolicy\PolicyInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnPolicyRequest;
use App\Http\Requests\UpdateReturnPolicyRequest;
use App\Http\Traits\SuccessResponse;
use App\Models\ReturnPolicy;
use App\Services\ReturnPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnPolicyController extends Controller
{
    use SuccessResponse;

    public function __construct(
        private ReturnPolicyService $service
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * List all return policies for the authenticated company.
     *
     * Validates: Requirements 1.1, 1.5
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->id;

        $policies = ReturnPolicy::where('company_id', $companyId)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($policies, 'تم جلب سياسات الاسترجاع بنجاح');
    }

    /**
     * Create a new return policy for the authenticated company.
     *
     * Validates: Requirements 1.1, 1.2, 1.3
     */
    public function store(StoreReturnPolicyRequest $request): JsonResponse
    {
        $companyId = $request->user()->id;
        $data = $request->validatedPayload();

        $policy = $this->service->create($companyId, $data);

        return $this->createdResponse($policy, 'تم إنشاء سياسة الاسترجاع بنجاح');
    }

    /**
     * Show a specific return policy.
     *
     * Validates: Requirements 1.1, 1.5
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->id;

        $policy = ReturnPolicy::where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if ($policy === null) {
            return response()->json([
                'success' => false,
                'message' => 'سياسة الاسترجاع غير موجودة أو لا تنتمي لشركتك',
            ], 404);
        }

        return $this->resourceResponse($policy, 'تم جلب سياسة الاسترجاع بنجاح');
    }

    /**
     * Update an existing return policy.
     *
     * Rejects with HTTP 422 if the policy is linked to existing invoices.
     *
     * Validates: Requirements 1.2, 1.3, 1.6, 1.8, 1.9
     */
    public function update(UpdateReturnPolicyRequest $request, int $id): JsonResponse
    {
        $companyId = $request->user()->id;

        $policy = ReturnPolicy::where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if ($policy === null) {
            return response()->json([
                'success' => false,
                'message' => 'سياسة الاسترجاع غير موجودة أو لا تنتمي لشركتك',
            ], 404);
        }

        try {
            $updated = $this->service->update($policy, $request->validatedPayload());
        } catch (PolicyInUseException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ], $e->getHttpStatus());
        }

        return $this->updatedResponse($updated, 'تم تحديث سياسة الاسترجاع بنجاح');
    }

    /**
     * Delete a return policy.
     *
     * Validates: Requirements 1.1
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->id;

        $policy = ReturnPolicy::where('id', $id)
            ->where('company_id', $companyId)
            ->first();

        if ($policy === null) {
            return response()->json([
                'success' => false,
                'message' => 'سياسة الاسترجاع غير موجودة أو لا تنتمي لشركتك',
            ], 404);
        }

        try {
            // Reject deletion if the policy is linked to existing invoices
            if ($policy->invoices()->exists()) {
                throw new PolicyInUseException();
            }

            $policy->delete();
        } catch (PolicyInUseException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ], $e->getHttpStatus());
        }

        return $this->deletedResponse('تم حذف سياسة الاسترجاع بنجاح');
    }
}
