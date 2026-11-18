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

    // Bunny Stream: product videos live here, never on the app server.
    'bunny' => [
        'library_id'      => env('BUNNY_LIBRARY_ID'),
        'api_key'         => env('BUNNY_API_KEY'),          // library API key (server side only)
        'embed_token_key' => env('BUNNY_EMBED_TOKEN_KEY'),  // Security > "Token authentication key" (embed view tokens)
        'cdn_host'        => env('BUNNY_CDN_HOST'),         // e.g. vz-xxxx.b-cdn.net (needed for the download button)
        'cdn_token_key'   => env('BUNNY_CDN_TOKEN_KEY'),    // optional: pull-zone token key if CDN token auth is on
        'token_ttl'       => (int) env('BUNNY_TOKEN_TTL', 21600),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
