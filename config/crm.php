<?php

return [
    'signup_domain' => env('CRM_SIGNUP_DOMAIN', 'paradigmcargo.com'),

    'ai' => [
        // OmniRoute (OpenAI-compatible). Switching Gemini -> Claude is a .env change only.
        'base_url' => env('AI_BASE_URL'),
        'api_key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'gemini-2.5-flash'),
        'vision_model' => env('AI_VISION_MODEL', env('AI_MODEL', 'gemini-2.5-flash')),
        'transcribe_model' => env('AI_TRANSCRIBE_MODEL', 'whisper-1'),
        'timeout' => (int) env('AI_TIMEOUT', 90),
        // USD per 1M tokens, for the monthly cost limit and the usage page.
        'price_in_per_m' => (float) env('AI_PRICE_IN_PER_M', 0.30),
        'price_out_per_m' => (float) env('AI_PRICE_OUT_PER_M', 2.50),
        'price_per_audio_minute' => (float) env('AI_PRICE_AUDIO_MIN', 0.006),
    ],

    'rates_mailbox' => [
        'enabled' => env('RATES_MAILBOX_ENABLED', false),
        'host' => env('RATES_IMAP_HOST', 'imap.gmail.com'),
        'port' => (int) env('RATES_IMAP_PORT', 993),
        'encryption' => env('RATES_IMAP_ENCRYPTION', 'ssl'),
        'username' => env('RATES_IMAP_USERNAME'),
        'password' => env('RATES_IMAP_PASSWORD'),
        'folder' => env('RATES_IMAP_FOLDER', 'INBOX'),
    ],

    'lead_api_key_header' => 'X-Lead-Key',

    'legacy' => [
        // Read-only connection to the old Supabase CRM, used by php artisan legacy:import
        'storage_url' => env('LEGACY_STORAGE_URL'),
        'storage_key' => env('LEGACY_STORAGE_KEY'),
    ],

    'staging_banner' => env('CRM_STAGING_BANNER', false),

    // cPanel shared hosting: no Supervisor, so the scheduler runs the queue worker every minute.
    'queue_via_scheduler' => env('QUEUE_VIA_SCHEDULER', false),
];
