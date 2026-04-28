<?php

namespace App\Services;

use App\Repositories\AdvertisementRepository;
use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdvertisementService
{
    protected AdvertisementRepository $advertisements;

    public function __construct(AdvertisementRepository $advertisements)
    {
        $this->advertisements = $advertisements;
    }

    public function all(array $with = [])
    {
        return $this->advertisements->all($with);
    }

    public function paginate(int $perPage = 15, array $with = [])
    {
        return $this->advertisements->paginate($perPage, $with);
    }

    public function query(?array $with = null): Builder
    {
        return $this->advertisements->query($with);
    }

    public function find($id, array $with = [])
    {
        return $this->advertisements->findOrFail($id, $with);
    }

    public function create(array $attributes)
    {
        return $this->advertisements->create($attributes);
    }

    public function update($id, array $attributes)
    {
        return $this->advertisements->update($id, $attributes);
    }

    public function delete($id)
    {
        return $this->advertisements->delete($id);
    }

    public function activate($id)
    {
        return $this->advertisements->activate($id);
    }

    public function deactivate($id)
    {
        return $this->advertisements->deactivate($id);
    }

    /**
     * جلب الإعلانات النشطة حسب الموقع (للموبايل/API)
     */
    public function getActiveByPosition(string $position): Collection
    {
        return Advertisement::active()
            ->position($position)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * جلب كل الإعلانات النشطة (للموبايل/API)
     */
    public function getAllActive(): Collection
    {
        return Advertisement::active()
            ->orderBy('position')
            ->orderBy('sort_order')
            ->get();
    }
}
