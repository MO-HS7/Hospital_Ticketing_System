<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit Type Mapping
    |--------------------------------------------------------------------------
    |
    | Maps short type names (stored in DB) to model classes.
    | Using short names makes DB queries cleaner and is resilient to namespace changes.
    |
    */
    'type_map' => [
        'ticket' => App\Models\Ticket::class,
        'user' => App\Models\User::class,
        'department' => App\Models\Department::class,
        'payment' => App\Models\Payment::class,
        'order' => App\Models\Order::class,
        'referral' => App\Models\Referral::class,
        'encounter' => App\Models\Encounter::class,
        'slot' => App\Models\Slot::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Excluded Fields
    |--------------------------------------------------------------------------
    |
    | Fields that should be excluded from ALL audit logs by default.
    | These are typically sensitive authentication/security fields.
    |
    */
    'default_exclude' => [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email_verified_at',
        'activation_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Masked Fields
    |--------------------------------------------------------------------------
    |
    | Fields whose values should be masked (shown as ***) in audit logs.
    | Used for fields that need to be tracked but contain sensitive data.
    |
    */
    'default_mask' => [
        'contact_phone',
        'phone',
        'mobile',
        'national_id',
        'ssn',
    ],

    /*
    |--------------------------------------------------------------------------
    | Noisy Fields
    |--------------------------------------------------------------------------
    |
    | Fields that when changed alone should not trigger an audit event.
    | Prevents spam from timestamp-only updates.
    |
    */
    'noisy_fields' => [
        'updated_at',
        'last_login_at',
        'last_activity_at',
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    */
    'export' => [
        // Maximum rows per export (0 = unlimited with date range required)
        'max_rows' => 10000,
        
        // Require date range for exports
        'require_date_range' => true,
        
        // Maximum date range in days
        'max_date_range_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Facets Settings
    |--------------------------------------------------------------------------
    */
    'facets' => [
        // Default state for include_facets parameter
        'default_enabled' => false,
    ],

];
