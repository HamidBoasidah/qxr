<?php

namespace App\Services;

use App\Repositories\AddressRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AddressService
{
    protected AddressRepository $addresses;

    public function __construct(AddressRepository $addresses)
    {
        $this->addresses = $addresses;
    }

    /**
     * Query عام (لو احتجته في حالات خاصة)
     */
    public function query(?array $with = null): Builder
    {
        return $this->addresses->query($with);
    }

    /**
     * تستخدم في لوحة التحكم أو أي مكان عام
     * - $with = null  => يستعمل defaultWith في AddressRepository
     * - $with = []    => بدون علاقات
     * - $with = ['..']=> علاقات مخصصة
     */
    public function all(?array $with = null)
    {
        return $this->addresses->all($with);
    }

    public function paginate(int $perPage = 15, ?array $with = null)
    {
        return $this->addresses->paginate($perPage, $with);
    }

    public function find(int|string $id, ?array $with = null)
    {
        return $this->addresses->findOrFail($id, $with);
    }

    /**
     * إنشاء عنوان جديد
     * - في الـ API: يربط العنوان بالمستخدم الحالي تلقائيًا إذا لم يُرسل user_id
     * - في لوحة التحكم: يمكن تمرير user_id من الفورم
     */
    public function create(array $attributes)
    {
        if (empty($attributes['user_id']) && Auth::check()) {
            $attributes['user_id'] = Auth::id();
        }

        $created = $this->addresses->create($attributes);

        // If the new address should be default, ensure it's set as default for the user
        if (!empty($attributes['is_default'])) {
            // Use the created record's user_id (in case it was injected)
            $this->setDefaultForUser($created->id, $created->user_id);
            // reload the created model with default relations
            return $this->addresses->findOrFail($created->id);
        }

        return $created;
    }

    /**
     * تحديث بالـ id (مناسب للـ Admin)
     */
    public function update(int|string $id, array $attributes)
    {
        return $this->addresses->update($id, $attributes);
    }

    /**
     * تحديث Model جاهز (مناسب للـ API بعد findForUser + Policy)
     */
    public function updateModel(Model $address, array $attributes)
    {
        // If the update requests this address to become the default,
        // perform the atomic default flip using the service method so other
        // addresses are unset. Otherwise perform a normal update.
        if (array_key_exists('is_default', $attributes) && $attributes['is_default']) {
            return $this->setDefaultForUser($address->id, $address->user_id);
        }

        return $this->addresses->updateModel($address, $attributes);
    }

    public function delete(int|string $id): bool
    {
        return $this->addresses->delete($id);
    }

    public function activate(int|string $id)
    {
        return $this->addresses->activate($id);
    }

    public function deactivate(int|string $id)
    {
        return $this->addresses->deactivate($id);
    }

    /**
     * 🔹 API: Query لعناوين مستخدم معيّن (index مع فلاتر)
     * - يرجع Builder عشان تقدر تطبق CanFilter و باقي الفلاتر
     * - يستفيد من defaultWith في AddressRepository لما $with = null
     */
    public function getQueryForUser(int $userId, ?array $with = null): Builder
    {
        return $this->addresses->forUser($userId, $with);
    }

    /**
     * (اختياري) لو حبيت تستعملها مباشرة بدون فلاتر إضافية
     */
    public function allForUser(int $userId, ?array $with = null)
    {
        return $this->addresses->allForUser($userId, $with);
    }

    public function paginateForUser(int $userId, int $perPage = 15, ?array $with = null)
    {
        return $this->addresses->paginateForUser($userId, $perPage, $with);
    }

    /**
     * 🔹 API: جلب عنوان مملوك لمستخدم معيّن (show / update / delete / activate / deactivate)
     */
    public function findForUser(int|string $id, int $userId, ?array $with = null)
    {
        return $this->addresses->findForUser($id, $userId, $with);
    }

    /**
     * Set the given address as the default for the specified user.
     * This will unset is_default on all other addresses of the user and
     * mark the target address as default. Throws ModelNotFoundException
     * if the address does not belong to the user.
     *
     * @param int|string $id
     * @param int $userId
     * @return \App\Models\Address
     */
    public function setDefaultForUser(int|string $id, int $userId)
    {
        return DB::transaction(function () use ($id, $userId) {
            // unset default flag for all this user's addresses
            $this->addresses->forUser($userId)->update(['is_default' => false]);

            // ensure the address belongs to this user (will throw ModelNotFoundException if not)
            $address = $this->addresses->findForUser($id, $userId);

            // mark the chosen address as default
            $this->addresses->updateModel($address, ['is_default' => true]);

            // return fresh model
            return $this->addresses->findOrFail($address->id);
        });
    }
}
