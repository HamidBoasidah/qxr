<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Services\ActivityLogService;
use App\DTOs\ActivityLogDTO;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:activitylogs.view')->only(['index', 'show']);
        $this->middleware('permission:activitylogs.delete')->only(['destroy']);
    }

    public function index(Request $request, ActivityLogService $activityLogService)
    {
        $perPage = (int) $request->input('per_page', 10);
        $activityLogs = $activityLogService->paginate($perPage);

        $activityLogs->getCollection()->transform(function ($activity) {
            return ActivityLogDTO::fromModel($activity)->toIndexArray();
        });

        return Inertia::render('Admin/ActivityLog/Index', [
            'activityLogs' => $activityLogs,
        ]);
    }

    public function create()
    {
        // لا يوجد إنشاء يدوي لسجلات النشاط، لذا يمكن تركه فارغًا أو إزالته إن أردت
        return back();
    }

    public function store(Request $request, ActivityLogService $activityLogService)
    {
        // لا يتم إنشاء Activity Log يدويًا
        return back();
    }

    public function show($id, ActivityLogService $activityLogService)
    {
        $activity = $activityLogService->find($id);
        $dto = ActivityLogDTO::fromModel($activity)->toArray();

        return Inertia::render('Admin/ActivityLog/Show', [
            'activity' => $dto,
        ]);
    }

    public function edit($id)
    {
        // لا يوجد تعديل لسجلات النشاط
        return back();
    }

    public function update(Request $request, ActivityLogService $activityLogService, $id)
    {
        // لا يتم التعديل على Activity Log
        return back();
    }

    public function destroy(ActivityLogService $activityLogService, $id)
    {
        $activityLogService->delete($id);

        return redirect()->route('admin.activitylogs.index');
    }

    public function activate($id)
    {
        // لا يوجد تفعيل/تعطيل في سجلات النشاط
        return back();
    }

    public function deactivate($id)
    {
        // لا يوجد تفعيل/تعطيل في سجلات النشاط
        return back();
    }
}
