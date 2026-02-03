<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreGovernorateRequest;
use App\Http\Requests\UpdateGovernorateRequest;
use App\Services\GovernorateService;
use App\DTOs\GovernorateDTO;
use App\Models\Governorate;
use Inertia\Inertia;

class GovernorateController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:governorates.view')->only(['index', 'show']);
        $this->middleware('permission:governorates.create')->only(['create', 'store']);
        $this->middleware('permission:governorates.update')->only(['edit', 'update' , 'activate', 'deactivate']);
        $this->middleware('permission:governorates.delete')->only(['destroy']);
    }

    public function index(Request $request, GovernorateService $governorateService)
    {
        $perPage = $request->input('per_page', 10);
        $governorates = $governorateService->paginate($perPage);
        $governorates->getCollection()->transform(function ($gov) {
            return GovernorateDTO::fromModel($gov)->toIndexArray();
        });
        return Inertia::render('Admin/Governorate/Index', [
            'governorates' => $governorates
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Governorate/Create');
    }

    public function store(StoreGovernorateRequest $request, GovernorateService $governorateService)
    {
        $data = $request->validated();
        $governorateService->create($data);
        return redirect()->route('admin.governorates.index');
    }

    public function show(Governorate $governorate)
    {
        $govDTO = GovernorateDTO::fromModel($governorate)->toArray();
        return Inertia::render('Admin/Governorate/Show', [
            'governorate' => $govDTO,
        ]);
    }

    public function edit(Governorate $governorate)
    {
        $govDTO = GovernorateDTO::fromModel($governorate)->toArray();
        return Inertia::render('Admin/Governorate/Edit', [
            'governorate' => $govDTO,
        ]);
    }

    public function update(UpdateGovernorateRequest $request, GovernorateService $governorateService, Governorate $governorate)
    {
        $data = $request->validated();
        $governorateService->update($governorate->id, $data);
        return redirect()->route('admin.governorates.index');
    }

    public function destroy(GovernorateService $governorateService, Governorate $governorate)
    {
        $governorateService->delete($governorate->id);
        return redirect()->route('admin.governorates.index');
    }

    public function activate(GovernorateService $governorateService, $id)
    {
        $governorateService->activate($id);
        return back()->with('success', 'Governorate activated successfully');
    }

    public function deactivate(GovernorateService $governorateService, $id)
    {
        $governorateService->deactivate($id);
        return back()->with('success', 'Governorate deactivated successfully');
    }
}
