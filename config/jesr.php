<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deployment Scope
    |--------------------------------------------------------------------------
    | 'national' - Full national deployment (36 states + FCT)
    | 'state'    - Single state deployment
    */
    'deployment_scope' => env('DEPLOYMENT_SCOPE', 'state'),

    /*
    |--------------------------------------------------------------------------
    | State Code (for single-state deployment)
    |--------------------------------------------------------------------------
    | Three-letter state code (e.g., 'JIG' for Jigawa)
    | Only used when deployment_scope = 'state'
    */
    'state_code' => env('STATE_CODE', 'JIG'),

    /*
    |--------------------------------------------------------------------------
    | Application Branding
    |--------------------------------------------------------------------------
    */
    'app_name' => env('JESR_APP_NAME', 'Jigawa Election Situation Room'),
    'app_short_name' => env('JESR_APP_SHORT', 'JESR'),

    /*
    |--------------------------------------------------------------------------
    | Correction Window
    |--------------------------------------------------------------------------
    | Number of minutes after submission during which an agent may still
    | edit a ward report without supervisor approval.
    */
    'correction_window_minutes' => env('CORRECTION_WINDOW_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Media Limits
    |--------------------------------------------------------------------------
    */
    'max_photos_per_report' => 5,
    'max_video_size_mb'     => 50,

    /*
    |--------------------------------------------------------------------------
    | SMS Driver
    |--------------------------------------------------------------------------
    | Supported: "africastalking", "sandbox"
    */
    'sms_driver' => env('SMS_DRIVER', 'sandbox'),

    'africastalking' => [
        'username'  => env('AFRICASTALKING_USERNAME', 'sandbox'),
        'api_key'   => env('AFRICASTALKING_API_KEY', ''),
        'sender_id' => env('AFRICASTALKING_SENDER_ID', 'JESR'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Escalation Response Timelines (minutes)
    |--------------------------------------------------------------------------
    | Maximum minutes allowed before an escalation at each level is
    | considered overdue.
    */
    'escalation_timelines' => [
        1 => 10,
        2 => 30,
        3 => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'report_submission_rate_limit' => 10, // requests per minute per agent

    /*
    |--------------------------------------------------------------------------
    | Map RAG Thresholds (% PUs reporting)
    |--------------------------------------------------------------------------
    | Green: >= 75% PUs have submitted reports
    | Amber: >= 40% PUs have submitted reports
    | Red:   < 40% PUs have submitted reports
    */
    'map_rag_thresholds' => [
        'green' => 75,
        'amber' => 40,
    ],

];
