<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UploadIconRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // يمكن إضافة منطق التحقق من الصلاحيات هنا
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'icon' => [
                'required',
                'file',
                'mimes:svg',
                'max:100', // 100KB
                function ($attribute, $value, $fail) {
                    if ($value instanceof UploadedFile) {
                        $this->validateSVGContent($value, $fail);
                    }
                }
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'icon.required' => 'يجب اختيار ملف الأيقونة',
            'icon.file' => 'يجب أن يكون الملف المرفوع ملف صالح',
            'icon.mimes' => 'يجب أن يكون الملف من نوع SVG',
            'icon.max' => 'حجم الملف كبير جداً. الحد الأقصى المسموح هو 100KB',
        ];
    }

    /**
     * Validate SVG content for security
     */
    private function validateSVGContent(UploadedFile $file, $fail): void
    {
        try {
            $content = $file->getContent();
            
            // التحقق من أن المحتوى XML صالح
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);
            
            if ($xml === false) {
                $fail('ملف SVG غير صالح أو تالف');
                return;
            }
            
            // التحقق من عدم وجود JavaScript أو عناصر خطيرة
            $dangerousPatterns = [
                '/<script[^>]*>.*?<\/script>/is',
                '/javascript:/i',
                '/on\w+\s*=/i',
                '/<iframe[^>]*>/i',
                '/<object[^>]*>/i',
                '/<embed[^>]*>/i',
                '/<link[^>]*>/i',
                '/<meta[^>]*>/i'
            ];
            
            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $fail('ملف SVG يحتوي على محتوى غير آمن');
                    return;
                }
            }
            
            // التحقق من أن الملف يحتوي على عنصر SVG
            if (!preg_match('/<svg[^>]*>/i', $content)) {
                $fail('الملف لا يحتوي على عنصر SVG صالح');
                return;
            }
            
        } catch (\Exception $e) {
            $fail('حدث خطأ أثناء التحقق من الملف');
        }
    }
}
