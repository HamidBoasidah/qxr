<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\DTOs\InvoiceDTO;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $companyId = $request->user()->id;

        $invoices = Invoice::with(['order.company:id,first_name,last_name', 'order.customer:id,first_name,last_name'])
            ->whereHas('order', function ($query) use ($companyId) {
                $query->where('company_user_id', $companyId);
            })
            ->latest()
            ->paginate($perPage);

        $invoices->getCollection()->transform(function (Invoice $invoice) {
            return InvoiceDTO::fromModel($invoice)->toIndexArray();
        });

        return Inertia::render('Company/Invoice/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function show($id)
    {
        $companyId = request()->user()->id;

        $invoice = Invoice::with([
            'order.company:id,first_name,last_name',
            'order.customer:id,first_name,last_name',
            'items.product:id,name',
            'bonusItems.product:id,name',
        ])
            ->whereHas('order', function ($query) use ($companyId) {
                $query->where('company_user_id', $companyId);
            })
            ->findOrFail($id);

        $detail = InvoiceDTO::fromModel($invoice)->toDetailArray();

        return Inertia::render('Company/Invoice/Show', [
            'invoice' => $detail,
        ]);
    }
}
