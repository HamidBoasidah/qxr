<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
// Logging removed per project request
use Illuminate\Validation\ValidationException;

class SVGIconService
{
    private const MAX_FILE_SIZE = 102400; // 100KB
    private const ALLOWED_MIME_TYPES = ['image/svg+xml', 'text/xml', 'application/xml'];
    private const STORAGE_PATH = 'category-icons';

    /**
     * Upload an SVG icon file
     */
    public function uploadIcon(UploadedFile $file, int $entityId, string $type = 'category', ?string $storagePath = null): string
    {
        try {
            // التحقق من صحة الملف
            $this->validateSVGFile($file);
            
            // إنشاء اسم ملف فريد
            $filename = $this->generateUniqueFilename($entityId, $file, $type);
            
            // حفظ الملف
            $path = $file->storeAs($storagePath ?? self::STORAGE_PATH, $filename, 'public');
            
            // تسجيل العملية الناجحة
            // SVG icon uploaded successfully (logging removed)
            
            return $path;
            
        } catch (ValidationException $e) {
            // تسجيل محاولة رفع ملف غير صالح
            // invalid SVG file upload attempt
            
            throw $e;
            
        } catch (\Exception $e) {
            // تسجيل الأخطاء غير المتوقعة
            // SVG icon upload failed
            
            throw new \Exception('فشل في رفع الأيقونة');
        }
    }

    /**
     * Delete an SVG icon file
     */
    public function deleteIcon(string $iconPath): bool
    {
        try {
            $result = Storage::disk('public')->delete($iconPath);
            
            // SVG icon deleted (logging removed)
            
            return $result;
        } catch (\Exception $e) {
            // Failed to delete SVG icon
            
            return false;
        }
    }

    /**
     * Validate SVG file
     */
    private function validateSVGFile(UploadedFile $file): void
    {
        // التحقق من الحجم
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'icon' => 'حجم الملف كبير جداً. الحد الأقصى المسموح هو 100KB'
            ]);
        }
        
        // التحقق من امتداد الملف
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension !== 'svg') {
            throw ValidationException::withMessages([
                'icon' => 'نوع الملف غير مدعوم. يجب أن يكون الملف من نوع SVG'
            ]);
        }
        
        // التحقق من نوع MIME
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw ValidationException::withMessages([
                'icon' => 'نوع الملف غير صالح'
            ]);
        }
        
        // التحقق من محتوى SVG
        $this->validateSVGContent($file->getContent());
    }

    /**
     * Validate SVG content for security
     */
    private function validateSVGContent(string $content): void
    {
        // التحقق من أن المحتوى XML صالح
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            
            throw ValidationException::withMessages([
                'icon' => 'ملف SVG غير صالح أو تالف'
            ]);
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
                // تسجيل محاولة رفع ملف مشبوه
                // Suspicious SVG file upload attempt detected
                
                throw ValidationException::withMessages([
                    'icon' => 'ملف SVG يحتوي على محتوى غير آمن'
                ]);
            }
        }
        
        // التحقق من أن الملف يحتوي على عنصر SVG
        if (!preg_match('/<svg[^>]*>/i', $content)) {
            throw ValidationException::withMessages([
                'icon' => 'الملف لا يحتوي على عنصر SVG صالح'
            ]);
        }
    }

    /**
     * Generate unique filename for the icon
     */
    private function generateUniqueFilename(int $entityId, UploadedFile $file, string $type = 'category'): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = time();
        $random = substr(md5(uniqid()), 0, 8);
        
        return "{$type}_{$entityId}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get the storage path for category icons
     */
    public function getStoragePath(): string
    {
        return self::STORAGE_PATH;
    }

    /**
     * Check if a file exists in storage
     */
    public function fileExists(string $path): bool
    {
        return Storage::disk('public')->exists($path);
    }

    /**
     * Get file URL
     */
    public function getFileUrl(string $path): string
    {
        return Storage::url($path);
    }
}