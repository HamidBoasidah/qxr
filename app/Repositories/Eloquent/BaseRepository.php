<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class BaseRepository implements BaseRepositoryInterface
{
    /**
     * الموديل الأساسي للـ Repository
     */
    protected Model $model;

    /**
     * العلاقات التي تُحمَّل تلقائيًا مع كل استعلام على هذا الـ Repository
     * إذا كانت null في الدوال العامة ⇒ تُستخدم هذه العلاقات
     */
    protected array $defaultWith = [];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * يبني Query مع تحكم كامل في العلاقات:
     * - null  => استخدم defaultWith
     * - []    => بدون علاقات
     * - array => استعمل هذه العلاقات فقط
     */

    /*
    استعلام خفيف بدون أي علاقات (مثلاً count):
    $count = $this->addresses->query([])->count();
    // هنا [] تعني "لا تستخدِم defaultWith"
    */
    /*
    استعلام بعلاقات مختلفة عن الافتراضية:
    $addresses = $this->addresses->paginate(10, ['governorate', 'district']);
    // هنا ستُستخدم العلاقات هذه فقط، بدون defaultWith (لأننا حدّدناها يدويًا)
    */
    protected function makeQuery(?array $with = null): Builder
    {
        if ($with === null) {
            // استخدم العلاقات الافتراضية
            $relations = $this->defaultWith;
        } else {
            // المستخدم يحدد بالضبط ما يريد (أو لا شيء)
            $relations = $with;
        }

        $query = $this->model->newQuery();

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    /**
     * إن احتجت Query خام (تستخدمه في أماكن أخرى)
     */
    public function query(?array $with = null): Builder
    {
        return $this->makeQuery($with);
    }

    public function all(?array $with = null)
    {
        return $this->makeQuery($with)->latest()->get();
    }

    public function paginate(int $perPage = 10, ?array $with = null)
    {
        return $this->makeQuery($with)->latest()->paginate($perPage);
    }

    public function find(int|string $id, ?array $with = null)
    {
        return $this->makeQuery($with)->find($id);
    }

    public function findOrFail(int|string $id, ?array $with = null)
    {
        return $this->makeQuery($with)->findOrFail($id);
    }

    /**
     * سجلات خاصة بمستخدم معيّن (يعتمد على وجود user_id في الجدول)
     */
    public function forUser(int $userId, ?array $with = null): Builder
    {
        return $this->makeQuery($with)->where('user_id', $userId);
    }

    /**
     * جميع السجلات الخاصة بمستخدم معيّن
     */
    public function allForUser(int $userId, ?array $with = null)
    {
        return $this->forUser($userId, $with)
            ->latest()
            ->get();
    }
    
    /**
     * ترقيم (paginate) لسجلات مستخدم معيّن
     */
    public function paginateForUser(int $userId, int $perPage = 10, ?array $with = null)
    {
        return $this->forUser($userId, $with)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * جلب سجل واحد يخص مستخدم معيّن أو يرمي ModelNotFoundException
     */
    public function findForUser(int|string $id, int $userId, ?array $with = null)
    {
        return $this->forUser($userId, $with)->findOrFail($id);
    }

    public function create(array $attributes)
    {
        $attributes = $this->handleFileUploads($attributes);
        return $this->model->create($attributes);
    }

    public function update(int|string $id, array $attributes)
    {
        $record = $this->findOrFail($id);
        $attributes = $this->handleFileUploads($attributes, $record);
        $record->update($attributes);
        return $record;
    }

    /**
     * مفيد للـ API لما يكون الـ Model جاهز عندك
     */
    public function updateModel(Model $record, array $attributes)
    {
        $attributes = $this->handleFileUploads($attributes, $record);
        $record->update($attributes);
        return $record;
    }

    public function delete(int|string $id): bool
    {
        $record = $this->findOrFail($id);
        return (bool) $record->delete();
    }

    public function activate(int|string $id)
    {
        $record = $this->findOrFail($id);
        $record->update(['is_active' => true]);
        return $record;
    }

    public function deactivate(int|string $id)
    {
        $record = $this->findOrFail($id);
        $record->update(['is_active' => false]);
        return $record;
    }

    /**
     * يعالج رفع الملفات ويستبدل كائن الملف بالمسار.
     */
    protected function handleFileUploads(array $attributes, ?Model $record = null): array
    {
        foreach ($attributes as $key => &$value) {
            if ($value instanceof UploadedFile) {
                // Determine storage strategy per-model / per-attribute:
                // Priority:
                // 1. If model defines method fileStorage(string $attribute) -> returns 'private'|'public'
                // 2. If model has array $privateFiles and attribute is listed -> 'private'
                // 3. If model has property $fileStorage = 'private' -> 'private'
                // 4. Default: 'public'
                $strategy = $this->getFileStorageForAttribute($key);

                // capture previous path (if any) for cross-disk cleanup
                $oldPath = $record && $record->{$key} ? $record->{$key} : null;

                if ($strategy === 'private') {
                    // If previous file existed on the public disk (previous strategy was public),
                    // remove it so we don't leave orphaned files when switching strategies.
                    if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }

                    // storePrivateFile will remove the old private file if it exists
                    $stored = $this->storePrivateFile($value, $oldPath, $this->model->getTable());
                    $value = $stored;
                } else {
                    // public disk behavior (backwards-compatible)

                    // If previous file existed on the local (private) disk, delete it to avoid orphaning.
                    if ($oldPath && Storage::disk('local')->exists($oldPath)) {
                        $this->deletePrivateFile($oldPath);
                    }

                    if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }

                    // تخزين الملف باسم UUID داخل مجلد باسم جدول الموديل
                    $filename = (string) Str::uuid() . '.' . $value->getClientOriginalExtension();
                    $path = $value->storeAs($this->model->getTable(), $filename, 'public');

                    $value = $path;
                }
            }
        }

        return $attributes;
    }

    /**
     * Determine storage strategy for a given attribute on the model.
     * Returns 'private' or 'public'.
     */
    protected function getFileStorageForAttribute(string $attribute): string
    {
        // 1) model method
        if (method_exists($this->model, 'fileStorage')) {
            try {
                $res = $this->model->fileStorage($attribute);
                if (in_array($res, ['private', 'public'], true)) {
                    return $res;
                }
            } catch (\Throwable $e) {
                // ignore and fall back
            }
        }

        // 2) per-attribute array on model
        if (property_exists($this->model, 'privateFiles') && is_array($this->model->privateFiles)) {
            if (in_array($attribute, $this->model->privateFiles, true)) {
                return 'private';
            }
        }

        // 3) global model preference
        if (property_exists($this->model, 'fileStorage') && $this->model->fileStorage === 'private') {
            return 'private';
        }

        return 'public';
    }

    /**
     * تخزين ملف خاص على disk local (storage/app)
     */
    protected function storePrivateFile(UploadedFile $file, ?string $oldPath = null, string $directory = 'private'): ?string
    {
        if (!$file->isValid()) {
            return null;
        }

        $disk = Storage::disk('local');

        if ($oldPath && $disk->exists($oldPath)) {
            $disk->delete($oldPath);
        }

        $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
        $fullPath = trim($directory, '/');
        $storedPath = $file->storeAs($fullPath, $filename, 'local');

        return $storedPath;
    }

    /**
     * حذف ملف من التخزين الخاص (local disk) إن وجد.
     */
    protected function deletePrivateFile(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        $disk = Storage::disk('local');

        if ($disk->exists($path)) {
            return $disk->delete($path);
        }

        return false;
    }
}
