<?php

namespace App\Http\Controllers\Api;

use App\DTOs\OrderDTO;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StaleDataException;
use App\Exceptions\TamperingException;
use App\Http\Controllers\Controller;
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

        $paginated->getCollection()->transform(fn ($order) => OrderDTO::fromModel($order));

        return $this->collectionResponse($paginated, 'تم جلب قائمة الطلبات بنجاح');
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $order = Order::with(['company', 'customer', 'items.product', 'items.bonuses.bonusProduct'])
                ->findOrFail($id);

            $this->authorize('view', $order);

            return $this->resourceResponse(
                $order->toArray(),
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

    /**
     * Create a new order
     * 
     * Requirements: 1.1, 1.3, 1.4, 10.1-10.8, 14.1-14.7
     */
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
            $order = Order::findOrFail($id);
            
            $this->authorize('update', $order);

            $order->update($request->validated());
            $order->load(['company', 'customer', 'items.product', 'items.bonuses.bonusProduct']);

            return $this->updatedResponse(
                $order->toArray(),
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
            $order = Order::findOrFail($id);
            
            $this->authorize('delete', $order);

            // حتى مع التفويض، إذا كان الطلب في حالة مقفلة لا نحذفه ونعيد 409
            $lockedStatuses = ['approved', 'preparing', 'shipped', 'delivered'];
            if (in_array($order->status, $lockedStatuses, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف طلب تم اعتماده أو جاري معالجته أو تم شحنه/تسليمه'
                ], 409);
            }

            $order->delete();

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
