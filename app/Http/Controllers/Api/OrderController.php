<?php

namespace App\Http\Controllers\Api;

use App\DTOs\OrderDTO;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\PreviewInvalidatedException;
use App\Exceptions\PreviewNotFoundException;
use App\Exceptions\PreviewOwnershipException;
use App\Exceptions\StaleDataException;
use App\Exceptions\TamperingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfirmOrderRequest;
use App\Http\Requests\Api\PreviewOrderRequest;
use App\Http\Requests\Api\StoreOrderRequest;
use App\Http\Requests\Api\UpdateOrderRequest;
use App\Http\Traits\CanFilter;
use App\Http\Traits\SuccessResponse;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use SuccessResponse, CanFilter;

    public function __construct(
        private OrderService $orderService,
        private OrderRepository $orders
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of orders
     * العميل يرى طلباته فقط، الشركة ترى الطلبات الموجهة لها
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $perPage = (int) $request->get('per_page', 10);
        $user = $request->user();

        $query = $this->orders->query($this->indexWith());

        // تصفية حسب نوع المستخدم
        if ($user->user_type === 'customer') {
            $query->where('customer_user_id', $user->id);
        } elseif ($user->user_type === 'company') {
            $query->where('company_user_id', $user->id);
        }

        // تطبيق الفلاتر
        $query = $this->applyFilters(
            $query,
            $request,
            $this->getSearchableFields(),
            $this->getForeignKeyFilters()
        );

        $paginated = $query->latest()->paginate($perPage);

        $paginated->getCollection()->transform(fn ($order) => OrderDTO::fromModel($order)->toArray());

        return $this->collectionResponse($paginated, 'تم جلب قائمة الطلبات بنجاح');
    }

    
    public function preview(PreviewOrderRequest $request): JsonResponse
    {
        try {
            $previewDTO = $this->orderService->previewOrder(
                $request->validated(),
                $request->user()
            );
            
            return response()->json([
                'success' => true,
                'data' => $previewDTO
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (NotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            Log::error('Order preview failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while previewing the order'
            ], 500);
        }
    }

    
    
    public function confirm(ConfirmOrderRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $orderDTO = $this->orderService->confirmOrder(
                $validated['preview_token'],
                $request->user(),
                (int) $validated['delivery_address_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => ['order' => $orderDTO]
            ], 201);
        } catch (PreviewInvalidatedException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'details' => $e->getDetails()
            ], 409);
        } catch (PreviewNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (PreviewOwnershipException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            Log::error('Order confirmation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while confirming the order'
            ], 500);
        }
    }


    /**
     * Display the specified order
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $order = $this->orders->findOrFail($id, $this->indexWith());

            $this->authorize('view', $order);

            return $this->resourceResponse(
                OrderDTO::fromModel($order)->toArray(),
                'تم جلب بيانات الطلب بنجاح'
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب المطلوب غير موجود'
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بعرض هذا الطلب'
            ], 403);
        }
    }

    
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Order::class);

            $orderDTO = $this->orderService->createOrder(
                $request->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح',
                'status_code' => 201,
                'data' => ['order' => $orderDTO]
            ], 201);
        } catch (StaleDataException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 409);
        } catch (TamperingException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->getErrors()
            ], 422);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        } catch (NotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الطلب'
            ], 500);
        }
    }

    /**
     * Update the specified order
     */
    public function update(UpdateOrderRequest $request, $id): JsonResponse
    {
        try {
            $order = $this->orders->findOrFail($id);
            
            $this->authorize('update', $order);

            $order = $this->orderService->updateOrder(
                $id,
                $request->validated(),
                $request->user()
            );
            $order->load($this->indexWith());

            return $this->updatedResponse(
                OrderDTO::fromModel($order)->toArray(),
                'تم تحديث الطلب بنجاح'
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب المطلوب غير موجود'
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل هذا الطلب'
            ], 403);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Order update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الطلب'
            ], 500);
        }
    }

    /**
     * Remove the specified order
     */
    public function destroy($id): JsonResponse
    {
        try {
            $order = $this->orders->findOrFail($id);
            
            $this->authorize('delete', $order);

            $this->orderService->deleteOrder($id);

            return $this->deletedResponse('تم حذف الطلب بنجاح');
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب المطلوب غير موجود'
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بحذف هذا الطلب'
            ], 403);
        } catch (\Exception $e) {
            Log::error('Order deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الطلب'
            ], 500);
        }
    }

    /**
     * Cancel an order (only if status is pending)
     */
    public function cancel(UpdateOrderRequest $request, $id): JsonResponse
    {
        try {
            $order = $this->orders->findOrFail($id);
            
            $this->authorize('cancel', $order);

            $order = $this->orderService->cancelOrder(
                $id,
                $request->user(),
                $request->validated()['notes_customer'] ?? null
            );
            $order->load($this->indexWith());

            return $this->updatedResponse(
                OrderDTO::fromModel($order)->toArray(),
                'تم إلغاء الطلب بنجاح'
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب المطلوب غير موجود'
            ], 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بإلغاء هذا الطلب'
            ], 403);
        } catch (\App\Exceptions\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Order cancellation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الطلب'
            ], 500);
        }
    }

    /**
     * العلاقات المطلوبة لقائمة الطلبات
     */
    protected function indexWith(): array
    {
        return [
            'company:id,first_name,last_name',
            'company.companyProfile:id,user_id,company_name',
            'customer:id,first_name,last_name',
            'items.product:id,name',
            'items.bonuses.bonusProduct:id,name',
            'items.bonuses.offer:id,title'
        ];
    }

    /**
     * حقول البحث النصي
     */
    protected function getSearchableFields(): array
    {
        return ['order_no', 'notes_customer', 'notes_company'];
    }

    /**
     * فلاتر المفاتيح الخارجية والقيم المنطقية
     */
    protected function getForeignKeyFilters(): array
    {
        return [
            'status' => 'status',
            'company_user_id' => 'company_user_id',
            'customer_user_id' => 'customer_user_id',
        ];
    }
}
