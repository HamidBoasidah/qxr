<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'message_id',
        'original_name',
        'mime_type',
        'size_bytes',
        'disk',
        'path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size_bytes' => 'integer',
    ];

    /**
     * Get the message that owns the attachment.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the download URL for the attachment.
     */
    public function getDownloadUrl(): string
    {
        return route('api.attachments.download', ['attachment' => $this->id]);
    }

    /**
     * Get the storage path for the attachment.
     */
    public function getStoragePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }
}
