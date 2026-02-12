<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chat Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where chat attachments should be stored.
    |
    */
    'storage' => [
        'disk' => env('CHAT_STORAGE_DISK', 'public'),
        'path' => env('CHAT_STORAGE_PATH', 'chat-attachments'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Attachments Configuration
    |--------------------------------------------------------------------------
    |
    | Configure attachment limits and allowed file types.
    |
    */
    'attachments' => [
        'max_files_per_message' => 10,
        'max_file_size_mb' => 10,
        'allowed_mime_types' => [
            // Images
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            
            // Archives
            'application/zip',
            'application/x-rar-compressed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Pagination
    |--------------------------------------------------------------------------
    |
    | Configure default pagination settings for conversations and messages.
    |
    */
    'pagination' => [
        'conversations_per_page' => 20,
        'messages_per_page' => 50,
    ],
];
