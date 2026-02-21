<?php

namespace App\Http\Controllers\Admin;

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
        $this->middleware('permission:orders.view')->only(['index', 'show']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $orders = $this->orders->paginate($perPage); // uses defaultWith

        $orders->getCollection()->transform(function (Order $order) {
            return AdminOrderDTO::fromModel($order)->toIndexArray();
        });

        return Inertia::render('Admin/Order/Index', [
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

        return Inertia::render('Admin/Order/Show', [
            'order' => $detail,
        ]);
    }
}
