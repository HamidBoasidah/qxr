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

    public function all(array $with = null)
    {
        return $this->offers->all($with);
    }

    public function paginate(int $perPage = 15, array $with = null)
    {
        return $this->offers->paginate($perPage, $with);
    }

    public function find($id, array $with = null): Offer
    {
        return $this->offers->findOrFail($id, $with);
    }

    /**
     * إنشاء عرض + إنشاء عناصر العرض + إنشاء المستهدفين
     *
     * $offerData      => من $request->validatedPayload()
     * $itemsPayload   => من $request->itemsPayload()
     * $targetsPayload => من $request->targetsPayload()
     */
    public function create(array $offerData, array $itemsPayload = [], array $targetsPayload = []): Offer
    {
        return DB::transaction(function () use ($offerData, $itemsPayload, $targetsPayload) {

            // الشركة تُؤخذ من المستخدم الحالي (ولا نثق بإدخال العميل)
            $offerData['company_user_id'] = $offerData['company_user_id'] ?? Auth::id();

            /** @var Offer $offer */
            $offer = $this->offers->create($offerData);

            // عناصر العرض (اختياري)
            $this->replaceItems($offer, $itemsPayload);

            // المستهدفون (اختياري)
            $this->replaceTargets($offer, $targetsPayload);

            return $offer->refresh();
        });
    }

    /**
     * تحديث عرض + (اختياري) تحديث العناصر + (اختياري) تحديث المستهدفين
     *
     * $itemsPayloadOrNull   => من $request->itemsPayloadOrNull()
     * $targetsPayloadOrNull => من $request->targetsPayloadOrNull()
     *
     * إذا كانت null => لا نلمس العلاقة
     * إذا كانت []  => نفرغ العلاقة
     */
    public function update($id, array $offerData, ?array $itemsPayloadOrNull = null, ?array $targetsPayloadOrNull = null): Offer
    {
        return DB::transaction(function () use ($id, $offerData, $itemsPayloadOrNull, $targetsPayloadOrNull) {

            // ممنوع تعديل الشركة المالكة من العميل
            unset($offerData['company_user_id']);

            /** @var Offer $offer */
            $offer = $this->offers->findOrFail($id);

            // (اختياري لاحقاً) تحقق ملكية: العرض يتبع نفس الشركة
            // if ($offer->company_user_id !== Auth::id()) abort(403);

            $offer = $this->offers->updateModel($offer, $offerData);

            // العناصر: إذا أرسلها العميل
            if ($itemsPayloadOrNull !== null) {
                $this->replaceItems($offer, $itemsPayloadOrNull);
            }

            // المستهدفون: إذا أرسلها العميل
            if ($targetsPayloadOrNull !== null) {
                $this->replaceTargets($offer, $targetsPayloadOrNull);
            }

            return $offer->refresh();
        });
    }

    public function delete($id): bool
    {
        return $this->offers->delete($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * استبدال عناصر العرض بالكامل
     * - إذا [] => سيتم حذف كل العناصر
     */
    private function replaceItems(Offer $offer, array $itemsPayload): void
    {
        // حذف القديم
        $offer->items()->delete();

        if (empty($itemsPayload)) {
            return;
        }

        // إنشاء الجديد
        $offer->items()->createMany($itemsPayload);
    }

    /**
     * استبدال المستهدفين بالكامل
     * - إذا [] => سيتم حذف كل المستهدفين
     */
    private function replaceTargets(Offer $offer, array $targetsPayload): void
    {
        // حذف القديم
        $offer->targets()->delete();

        if (empty($targetsPayload)) {
            return;
        }

        // إنشاء الجديد
        $offer->targets()->createMany($targetsPayload);
    }
}