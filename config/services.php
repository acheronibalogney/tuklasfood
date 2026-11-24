<?php

return [

    'cooking_ai' => [
        'provider' => env('AI_PROVIDER', 'gemini'),
        'key' => env('AI_API_KEY'),
        'model' => env('AI_TEXT_MODEL') ?: 'gemini-3.7-flash',
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('AI_FALLBACK_TEXT_MODELS', 'gemini-3.5-flash,gemini-3.1-flash-lite')),
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
