<?php

namespace App\Services;

use App\Models\Offer;
use App\Repositories\OfferRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OfferService
{
    protected OfferRepository $offers;

    public function __construct(OfferRepository $offers)
    {
        $this->offers = $offers;
    }

    /**
     * ✅ Index: خفيف + withCount داخل الريبوزيتوري
     */
    public function paginateForIndex(int $perPage = 15, ?int $companyUserId = null)
    {
        return $this->offers->paginateForIndex($perPage, $companyUserId);
    }

    /**
     * ✅ Show/Edit: كامل داخل الريبوزيتوري
     */
    public function findForShow(int $id): Offer
    {
        return $this->offers->findForShow($id);
    }

    public function create(array $offerData, array $itemsPayload = [], array $targetsPayload = []): Offer
    {
        return DB::transaction(function () use ($offerData, $itemsPayload, $targetsPayload) {

            $offerData['company_user_id'] = $offerData['company_user_id'] ?? Auth::id();

            /** @var Offer $offer */
            $offer = $this->offers->create($offerData);

            $this->replaceItems($offer, $itemsPayload);
            $this->replaceTargets($offer, $targetsPayload);

            return $offer->refresh();
        });
    }

    public function update(int $id, array $offerData, ?array $itemsPayloadOrNull = null, ?array $targetsPayloadOrNull = null): Offer
    {
        return DB::transaction(function () use ($id, $offerData, $itemsPayloadOrNull, $targetsPayloadOrNull) {

            unset($offerData['company_user_id']);

            /** @var Offer $offer */
            $offer = Offer::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $offer = $this->offers->updateModel($offer, $offerData);

            if ($itemsPayloadOrNull !== null) {
                $this->replaceItems($offer, $itemsPayloadOrNull);
            }

            if ($targetsPayloadOrNull !== null) {
                $this->replaceTargets($offer, $targetsPayloadOrNull);
            }

            return $offer->refresh();
        });
    }

    public function delete(int $id): bool
    {
        return $this->offers->delete($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */

    private function replaceItems(Offer $offer, array $itemsPayload): void
    {
        // استخدام forceDelete لأن soft delete غير مناسب هنا
        $offer->items()->forceDelete();

        if (empty($itemsPayload)) {
            return;
        }

        $offer->items()->createMany($itemsPayload);
    }

    private function replaceTargets(Offer $offer, array $targetsPayload): void
    {
        // استخدام forceDelete لأن soft delete غير مناسب هنا
        $offer->targets()->forceDelete();

        if (empty($targetsPayload)) {
            return;
        }

        $offer->targets()->createMany($targetsPayload);
    }
}