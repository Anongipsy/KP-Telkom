<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Storage Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk where contract documents will be stored.
    | 'local' points to storage/app/private by default in Laravel 12.
    |
    */
    'disk' => env('DOCUMENT_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Document Storage Path
    |--------------------------------------------------------------------------
    |
    | The relative directory path on the storage disk.
    |
    */
    'path' => env('DOCUMENT_PATH', 'documents'),

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types & Extensions
    |--------------------------------------------------------------------------
    |
    | Document file types accepted for contract attachments.
    |
    */
    'allowed_mime_types' => [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
    ],

    'allowed_extensions' => [
        'pdf',
        'png',
        'jpg',
        'jpeg',
        'webp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Upload File Size (MB and KB)
    |--------------------------------------------------------------------------
    |
    | 10MB maximum file size limit as specified.
    |
    */
    'max_upload_size_mb' => (int) env('DOCUMENT_MAX_UPLOAD_MB', 10),
    'max_upload_size_kb' => (int) env('DOCUMENT_MAX_UPLOAD_KB', 10240),
];
