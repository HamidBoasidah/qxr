<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Services\TagService;
use App\DTOs\TagDTO;
use App\Models\Tag;
use Inertia\Inertia;

class TagController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tags.view')->only(['index', 'show']);
        $this->middleware('permission:tags.create')->only(['create', 'store']);
        $this->middleware('permission:tags.update')->only(['edit', 'update', 'activate', 'deactivate']);
        $this->middleware('permission:tags.delete')->only(['destroy']);
    }

    public function index(Request $request, TagService $tagService)
    {
        $perPage = $request->input('per_page', 10);
        $tags = $tagService->paginate($perPage);
        $tags->getCollection()->transform(function ($tag) {
            return TagDTO::fromModel($tag)->toIndexArray();
        });
        return Inertia::render('Admin/Tag/Index', [
            'tags' => $tags
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Tag/Create');
    }

    public function store(StoreTagRequest $request, TagService $tagService)
    {
        $data = $request->validated();
        $tagService->create($data);
        return redirect()->route('admin.tags.index');
    }

    public function show(Tag $tag)
    {
        $dto = TagDTO::fromModel($tag)->toArray();
        return Inertia::render('Admin/Tag/Show', [
            'tag' => $dto,
        ]);
    }

    public function edit(Tag $tag)
    {
        $dto = TagDTO::fromModel($tag)->toArray();
        return Inertia::render('Admin/Tag/Edit', [
            'tag' => $dto,
        ]);
    }

    public function update(UpdateTagRequest $request, TagService $tagService, Tag $tag)
    {
        $data = $request->validated();
        $tagService->update($tag->id, $data);
        return redirect()->route('admin.tags.index');
    }

    public function destroy(TagService $tagService, Tag $tag)
    {
        $tagService->delete($tag->id);
        return redirect()->route('admin.tags.index');
    }

    public function activate(TagService $tagService, $id)
    {
        $tagService->activate($id);
        return back()->with('success', 'Tag activated successfully');
    }

    public function deactivate(TagService $tagService, $id)
    {
        $tagService->deactivate($id);
        return back()->with('success', 'Tag deactivated successfully');
    }
}
