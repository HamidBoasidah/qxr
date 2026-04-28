<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvertisementRequest;
use App\Http\Requests\UpdateAdvertisementRequest;
use App\Services\AdvertisementService;
use App\DTOs\AdvertisementDTO;
use App\Models\Advertisement;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdvertisementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:advertisements.view')->only(['index', 'show']);
        $this->middleware('permission:advertisements.create')->only(['create', 'store']);
        $this->middleware('permission:advertisements.update')->only(['edit', 'update', 'activate', 'deactivate']);
        $this->middleware('permission:advertisements.delete')->only(['destroy']);
    }

    public function index(Request $request, AdvertisementService $service)
    {
        $perPage = $request->input('per_page', 10);
        $ads = $service->paginate($perPage);
        $ads->getCollection()->transform(fn($ad) => AdvertisementDTO::fromModel($ad)->toIndexArray());

        return Inertia::render('Admin/Advertisement/Index', [
            'advertisements' => $ads,
        ]);
    }

    public function create()
    {
        $companies = User::where('user_type', 'company')->select('id', 'first_name', 'last_name')->get();
        return Inertia::render('Admin/Advertisement/Create', [
            'companies' => $companies,
        ]);
    }

    public function store(StoreAdvertisementRequest $request, AdvertisementService $service)
    {
        $data = $request->validated();
        $data['image'] = $request->file('image');
        $data['image_path'] = $data['image'];
        unset($data['image']);

        $service->create($data);
        return redirect()->route('admin.advertisements.index');
    }

    public function show(Advertisement $advertisement)
    {
        $advertisement->load('company');
        return Inertia::render('Admin/Advertisement/Show', [
            'advertisement' => AdvertisementDTO::fromModel($advertisement)->toArray(),
        ]);
    }

    public function edit(Advertisement $advertisement)
    {
        $advertisement->load('company');
        $companies = User::where('user_type', 'company')->select('id', 'first_name', 'last_name')->get();
        return Inertia::render('Admin/Advertisement/Edit', [
            'advertisement' => AdvertisementDTO::fromModel($advertisement)->toArray(),
            'companies' => $companies,
        ]);
    }

    public function update(UpdateAdvertisementRequest $request, AdvertisementService $service, Advertisement $advertisement)
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image');
        }
        unset($data['image']);

        $service->update($advertisement->id, $data);
        return redirect()->route('admin.advertisements.index');
    }

    public function destroy(AdvertisementService $service, Advertisement $advertisement)
    {
        $service->delete($advertisement->id);
        return redirect()->route('admin.advertisements.index');
    }

    public function activate(AdvertisementService $service, $id)
    {
        $service->activate($id);
        return back();
    }

    public function deactivate(AdvertisementService $service, $id)
    {
        $service->deactivate($id);
        return back();
    }
}
