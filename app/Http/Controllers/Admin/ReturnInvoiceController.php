<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReturnInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $returnInvoices = ReturnInvoice::with([
            'company:id,first_name,last_name',
            'originalInvoice',
        ])
            ->latest()
            ->paginate($perPage);

        return Inertia::render('Admin/ReturnInvoice/Index', [
            'returnInvoices' => $returnInvoices,
        ]);
    }

    public function show($id)
    {
        $returnInvoice = ReturnInvoice::with([
            'company:id,first_name,last_name',
            'originalInvoice',
            'items',
        ])->findOrFail($id);

        return Inertia::render('Admin/ReturnInvoice/Show', [
            'returnInvoice' => $returnInvoice,
        ]);
    }
}
