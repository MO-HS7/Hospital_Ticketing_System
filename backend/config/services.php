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

    /*
    |--------------------------------------------------------------------------
    | You Agent Service (Express.js with You.com Search API)
    |--------------------------------------------------------------------------
    |
    | Configuration for the You Agent Express service for RAG-style Q&A.
    | This is the primary AI provider when configured.
    |
    */

    'you_agent' => [
        'url' => env('YOU_AGENT_URL'), // e.g. http://localhost:3100
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Gemini AI Service (Fallback)
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Gemini AI integration in the chatbot.
    | Used as fallback when You Agent is not available.
    | Set CHATBOT_AI_ENABLED=true to enable AI-powered symptom analysis.
    |
    */

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],

    'chatbot' => [
        'ai_enabled' => env('CHATBOT_AI_ENABLED', false),
    ],

];
