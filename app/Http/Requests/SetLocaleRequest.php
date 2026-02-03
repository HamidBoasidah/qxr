<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetLocaleRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['locale' => ['required','string','in:ar,en']]; }
    public function locale(): string { return $this->validated()['locale']; }
}

