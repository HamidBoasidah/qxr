<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReturnInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'original_invoice_id'        => ['required', 'integer', 'exists:invoices,id'],
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id'     => ['required', 'integer', 'exists:invoice_items,id'],
            'items.*.quantity'            => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'original_invoice_id.required'       => 'معرف الفاتورة الأصلية مطلوب.',
            'original_invoice_id.integer'        => 'معرف الفاتورة الأصلية يجب أن يكون عدداً صحيحاً.',
            'original_invoice_id.exists'         => 'الفاتورة الأصلية غير موجودة.',
            'items.required'                     => 'قائمة البنود مطلوبة.',
            'items.array'                        => 'البنود يجب أن تكون مصفوفة.',
            'items.min'                          => 'يجب تضمين بند واحد على الأقل.',
            'items.*.invoice_item_id.required'   => 'معرف بند الفاتورة مطلوب لكل بند.',
            'items.*.invoice_item_id.integer'    => 'معرف بند الفاتورة يجب أن يكون عدداً صحيحاً.',
            'items.*.invoice_item_id.exists'     => 'أحد بنود الفاتورة غير موجود.',
            'items.*.quantity.required'          => 'الكمية مطلوبة لكل بند.',
            'items.*.quantity.integer'           => 'الكمية يجب أن تكون عدداً صحيحاً.',
            'items.*.quantity.min'               => 'الكمية يجب أن تكون عدداً موجباً (1 على الأقل).',
        ];
    }

    public function validatedPayload(): array
    {
        return $this->only(['original_invoice_id', 'items']);
    }
}
