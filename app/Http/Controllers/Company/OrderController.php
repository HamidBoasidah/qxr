<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use App\DTOs\OrderDetailDTO;

class OrderController extends Controller
{
    public function __construct(
        private OrderRepository $orders,
        private OrderService $orderService,
    ) {
        $this->middleware('auth:web');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $companyId = $request->user()->id;

        $orders = $this->orders
            ->query() // includes defaultWith
            ->where('company_user_id', $companyId)
            ->latest()
            ->paginate($perPage);

        $orders->getCollection()->transform(function (Order $order) {
            return OrderDetailDTO::fromModel($order)->toIndexArray();
        });

        return Inertia::render('Company/Order/Index', [
            'orders' => $orders,
        ]);
    }

    public function show($id)
    {
        $with = array_merge(
            $this->orders->getDefaultWith(),
            [
                'items.product:id,name,unit_name',
                'statusLogs.changedBy:id,first_name,last_name',
                'deliveryAddress.governorate:id,name',
                'deliveryAddress.district:id,name',
                'deliveryAddress.area:id,name',
            ]
        );

        $order = $this->orders->findOrFail($id, $with);

        $detail = OrderDetailDTO::fromModel($order)->toDetailArray();

        return Inertia::render('Company/Order/Show', [
            'order' => $detail,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'string'],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->orderService->updateStatusByCompany(
                (int) $id,
                $request->input('status'),
                $request->user()->id,
                $request->input('note'),
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (ValidationException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', true);
    }
}
