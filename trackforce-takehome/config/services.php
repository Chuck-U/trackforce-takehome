<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tracktik' => [
        'client_id' => env('TRACKTIK_CLIENT_ID', 'tracktik_test_728e5c61ad2543d6'),
        'client_secret' => env('TRACKTIK_CLIENT_SECRET', 'secret_4de5efe9c29941a9849278f321830e59'),
        'token_url' => env('TRACKTIK_TOKEN_URL', 'https://cnvc2vp9q8.execute-api.us-east-2.amazonaws.com/prod/oauth/token'),
        'base_url' => env('TRACKTIK_BASE_URL', 'https://cnvc2vp9q8.execute-api.us-east-2.amazonaws.com/prod/v1'),
        'scope' => env('TRACKTIK_SCOPE', 'employees:read employees:write'),
    ],

];
