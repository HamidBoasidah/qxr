<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\MessageAttachment;
use App\Repositories\AttachmentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachmentService
{
    protected AttachmentRepository $attachments;

    public function __construct(AttachmentRepository $attachments)
    {
        $this->attachments = $attachments;
    }

    /**
     * Store multiple attachments for a message
     * Returns array of attachment metadata
     * 
     * @param int $messageId
     * @param array $uploadedFiles Array of UploadedFile instances
     * @return array Array of MessageAttachment models
     */
    public function storeAttachments(int $messageId, array $uploadedFiles): array
    {
        $attachments = [];

        foreach ($uploadedFiles as $file) {
            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Store file to private disk
            $disk = config('chat.storage.disk', 'private');
            $basePath = config('chat.storage.path', 'chat-attachments');
            $path = $basePath . '/' . $messageId . '/' . $filename;
            
            Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

            // Create attachment record
            $attachment = $this->attachments->create([
                'message_id' => $messageId,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'disk' => $disk,
                'path' => $path,
            ]);

            $attachments[] = $attachment;
        }

        return $attachments;
    }

    /**
     * Validate uploaded files
     * Checks: mime types, file sizes, count limits
     * 
     * @param array $uploadedFiles
     * @throws ValidationException
     */
    public function validateFiles(array $uploadedFiles): void
    {
        $maxFiles = config('chat.attachments.max_files_per_message', 5);
        $maxSizeMb = config('chat.attachments.max_file_size_mb', 25);
        $allowedMimeTypes = config('chat.attachments.allowed_mime_types', []);

        // Check file count
        if (count($uploadedFiles) > $maxFiles) {
            throw ValidationException::withMessages([
                'files' => ["الحد الأقصى {$maxFiles} ملفات لكل رسالة"]
            ]);
        }

        foreach ($uploadedFiles as $file) {
            if (!($file instanceof UploadedFile)) {
                throw ValidationException::withMessages([
                    'files' => ['ملف غير صالح']
                ]);
            }

            // Check file size
            $maxSizeBytes = $maxSizeMb * 1024 * 1024;
            if ($file->getSize() > $maxSizeBytes) {
                throw ValidationException::withMessages([
                    'files' => ["الملف {$file->getClientOriginalName()} يتجاوز الحد الأقصى {$maxSizeMb} ميجابايت"]
                ]);
            }

            // Check mime type
            if (!empty($allowedMimeTypes) && !in_array($file->getMimeType(), $allowedMimeTypes)) {
                throw ValidationException::withMessages([
                    'files' => ["نوع الملف {$file->getMimeType()} غير مسموح"]
                ]);
            }
        }
    }

    /**
     * Get secure download response for attachment
     * 
     * @param MessageAttachment $attachment
     * @return Response
     */
    public function downloadAttachment(MessageAttachment $attachment): Response
    {
        $disk = Storage::disk($attachment->disk);
        
        if (!$disk->exists($attachment->path)) {
            throw new \App\Exceptions\NotFoundException('الملف غير موجود');
        }

        $fileContents = $disk->get($attachment->path);
        
        return response($fileContents, 200, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'attachment; filename="' . $attachment->original_name . '"',
        ]);
    }
}
