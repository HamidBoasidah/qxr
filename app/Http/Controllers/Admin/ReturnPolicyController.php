<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnPolicy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReturnPolicyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $policies = ReturnPolicy::with('company:id,first_name,last_name')
            ->latest()
            ->paginate($perPage);

        return Inertia::render('Admin/ReturnPolicy/Index', [
            'policies' => $policies,
        ]);
    }

    public function show($id)
    {
        $policy = ReturnPolicy::with('company:id,first_name,last_name')
            ->findOrFail($id);

        return Inertia::render('Admin/ReturnPolicy/Show', [
            'policy' => $policy,
        ]);
    }
}
