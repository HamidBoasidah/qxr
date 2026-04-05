<?php

namespace App\Http\Controllers\Company;

use App\Exceptions\ReturnPolicy\PolicyInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReturnPolicyRequest;
use App\Http\Requests\UpdateReturnPolicyRequest;
use App\Models\ReturnPolicy;
use App\Services\ReturnPolicyService;
use Inertia\Inertia;

class ReturnPolicyController extends Controller
{
    public function __construct(
        private ReturnPolicyService $returnPolicyService
    ) {
        $this->middleware('auth:web');
    }

    public function index()
    {
        $policies = ReturnPolicy::where('company_id', auth()->id())
            ->latest()
            ->paginate(10);

        return Inertia::render('Company/ReturnPolicy/Index', [
            'policies' => $policies,
        ]);
    }

    public function create()
    {
        return Inertia::render('Company/ReturnPolicy/Create');
    }

    public function store(StoreReturnPolicyRequest $request)
    {
        $this->returnPolicyService->create(auth()->id(), $request->validatedPayload());

        return redirect()->route('company.return-policies.index')
            ->with('success', true);
    }

    public function show($id)
    {
        $policy = ReturnPolicy::findOrFail($id);

        if ($policy->company_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Company/ReturnPolicy/Show', [
            'policy' => $policy,
        ]);
    }

    public function edit($id)
    {
        $policy = ReturnPolicy::findOrFail($id);

        if ($policy->company_id !== auth()->id()) {
            abort(403);
        }

        return Inertia::render('Company/ReturnPolicy/Edit', [
            'policy' => $policy,
        ]);
    }

    public function update(UpdateReturnPolicyRequest $request, $id)
    {
        $policy = ReturnPolicy::findOrFail($id);

        if ($policy->company_id !== auth()->id()) {
            abort(403);
        }

        try {
            $this->returnPolicyService->update($policy, $request->validatedPayload());
        } catch (PolicyInUseException $e) {
            abort(422, $e->getMessage());
        }

        return redirect()->route('company.return-policies.index')
            ->with('success', true);
    }

    public function destroy($id)
    {
        $policy = ReturnPolicy::findOrFail($id);

        if ($policy->company_id !== auth()->id()) {
            abort(403);
        }

        if ($policy->returnInvoices()->exists()) {
            return back()->withErrors([
                'message' => 'Cannot delete a policy that is linked to existing return invoices',
            ])->setStatusCode(422);
        }

        $policy->delete();

        return redirect()->route('company.return-policies.index')
            ->with('success', true);
    }
}
