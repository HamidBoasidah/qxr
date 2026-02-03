<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:permissions.view')->only(['index']);
    }

    public function index()
    {
        return Inertia::render('Admin/Permission/Index', [
            'acl' => [
                'resources'       => config('acl.resources'),
                'resource_labels' => config('acl.resource_labels'),
                'action_labels'   => config('acl.action_labels'),
            ],
        ]);
    }

}
