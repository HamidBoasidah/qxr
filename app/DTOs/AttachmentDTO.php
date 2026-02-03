<?php

namespace App\DTOs;

use App\Models\MessageAttachment;

class AttachmentDTO extends BaseDTO
{
    public int $id;
    public string $original_name;
    public string $mime_type;
    public int $size_bytes;
    public string $download_url;

    public function __construct(
        int $id,
        string $original_name,
        string $mime_type,
        int $size_bytes,
        string $download_url
    ) {
        $this->id = $id;
        $this->original_name = $original_name;
        $this->mime_type = $mime_type;
        $this->size_bytes = $size_bytes;
        $this->download_url = $download_url;
    }

    /**
     * Create DTO from MessageAttachment model
     */
    public static function fromModel(MessageAttachment $attachment): self
    {
        return new self(
            id: $attachment->id,
            original_name: $attachment->original_name,
            mime_type: $attachment->mime_type,
            size_bytes: $attachment->size_bytes,
            download_url: $attachment->getDownloadUrl()
        );
    }

    /**
     * Convert DTO to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'download_url' => $this->download_url,
        ];
    }
}
