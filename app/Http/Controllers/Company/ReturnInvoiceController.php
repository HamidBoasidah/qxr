<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\ReturnInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReturnInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index(Request $request)
    {
        $perPage   = (int) $request->input('per_page', 15);
        $companyId = $request->user()->id;

        $returnInvoices = ReturnInvoice::with(['originalInvoice'])
            ->where('company_id', $companyId)
            ->latest()
            ->paginate($perPage);

        return Inertia::render('Company/ReturnInvoice/Index', [
            'returnInvoices' => $returnInvoices,
        ]);
    }

    public function show($id)
    {
        $companyId = request()->user()->id;

        $returnInvoice = ReturnInvoice::with(['items', 'company', 'originalInvoice'])
            ->findOrFail($id);

        if ((int) $returnInvoice->company_id !== (int) $companyId) {
            abort(403);
        }

        return Inertia::render('Company/ReturnInvoice/Show', [
            'returnInvoice' => $returnInvoice,
        ]);
    }

    public function approve($id)
    {
        $companyId = request()->user()->id;

        $returnInvoice = ReturnInvoice::findOrFail($id);

        if ((int) $returnInvoice->company_id !== (int) $companyId) {
            abort(403);
        }

        if ($returnInvoice->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending return invoices can be approved'])->setStatusCode(422);
        }

        $returnInvoice->update(['status' => 'approved']);

        return redirect()->back()->with('success', true);
    }

    public function reject($id)
    {
        $companyId = request()->user()->id;

        $returnInvoice = ReturnInvoice::findOrFail($id);

        if ((int) $returnInvoice->company_id !== (int) $companyId) {
            abort(403);
        }

        if ($returnInvoice->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending return invoices can be rejected'])->setStatusCode(422);
        }

        $returnInvoice->update(['status' => 'rejected']);

        return redirect()->back()->with('success', true);
    }
}
