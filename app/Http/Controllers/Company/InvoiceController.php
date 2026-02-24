<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\DTOs\InvoiceDTO;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {
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

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:paid,void'],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->invoiceService->updateStatusByCompany(
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
