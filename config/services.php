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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],
    'ses' => [
        'key' => env('AWS_MAIL_ACCESS_KEY_ID'),
        'secret' => env('AWS_MAIL_SECRET_ACCESS_KEY'),
        'region' => env('AWS_MAIL_DEFAULT_REGION', 'ap-south-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID','695519589827-2irj42ss0ip7kqt5n203vkbbortokb18.apps.googleusercontent.com'),
        'client_secret' => env('GOOGLE_SECRET','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'),
        'redirect' => env('GOOGLE_REDIRECT_URL','https://stage-api.bookmyinfluencers.com/api/v2/social/login/google/callback'),
    ],

    'facebook' => [
        'client_id' => '786554066225031',
        'client_secret' => 'cd7941a31cc956a9dba7b51fd754e049',
        'redirect' => 'http://127.0.0.1:8000/auth/facebook/callback',
    ],
];
