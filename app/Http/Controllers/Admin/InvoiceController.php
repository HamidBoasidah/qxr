<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\DTOs\InvoiceDTO;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:invoices.view')->only(['index', 'show']);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $invoices = Invoice::with(['order.company:id,first_name,last_name', 'order.customer:id,first_name,last_name'])
            ->latest()
            ->paginate($perPage);

        $invoices->getCollection()->transform(function (Invoice $invoice) {
            return InvoiceDTO::fromModel($invoice)->toIndexArray();
        });

        return Inertia::render('Admin/Invoice/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function show($id)
    {
        $invoice = Invoice::with([
            'order.company:id,first_name,last_name',
            'order.customer:id,first_name,last_name',
            'items.product:id,name',
            'bonusItems.product:id,name',
        ])->findOrFail($id);

        $detail = InvoiceDTO::fromModel($invoice)->toDetailArray();

        return Inertia::render('Admin/Invoice/Show', [
            'invoice' => $detail,
        ]);
    }
}
