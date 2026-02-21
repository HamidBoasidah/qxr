<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Repositories\OrderRepository;
use App\DTOs\AdminOrderDTO;

class OrderController extends Controller
{
    public function __construct(private OrderRepository $orders)
    {
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
            return AdminOrderDTO::fromModel($order)->toIndexArray();
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
            ]
        );

        $order = $this->orders->findOrFail($id, $with);

        $detail = AdminOrderDTO::fromModel($order)->toDetailArray();

        return Inertia::render('Company/Order/Show', [
            'order' => $detail,
        ]);
    }
}
