<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // التحقق الحقيقي بيكون داخل ChatService حسب اتفاقنا
    }

    public function rules(): array
    {
        return []; // ما في body، كل شيء من route
    }
}