<?php

/**
 * Google API Configuration — PRD Section 10, 15
 *
 * All values loaded from .env — no hardcoded credentials.
 * Separate DEV and PROD Google Cloud projects recommended.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Service Account
    |--------------------------------------------------------------------------
    */
    'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON'),
    'project_id' => env('GOOGLE_PROJECT_ID'),
    'client_email' => env('GOOGLE_CLIENT_EMAIL'),
    'private_key' => env('GOOGLE_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Google Sheets — Single Source of Truth (Contract Data)
    |--------------------------------------------------------------------------
    */
    'sheets' => [
        'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
        'sheet_name' => env('GOOGLE_SHEETS_SHEET_NAME'),
        'range' => env('GOOGLE_SHEETS_RANGE'),
        'header_row' => (int) env('GOOGLE_SHEETS_HEADER_ROW', 1),
        'cache_ttl' => (int) env('GOOGLE_SHEETS_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Drive — Document Storage — PRD FR-12, FR-13
    |--------------------------------------------------------------------------
    */
    'drive' => [
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
        'allowed_mime_types' => [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp',
        ],
        'max_preview_size_mb' => 25,
    ],

];
