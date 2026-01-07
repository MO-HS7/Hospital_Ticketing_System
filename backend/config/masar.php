<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment System
    |--------------------------------------------------------------------------
    |
    | Controls whether payment is required before appointment ticket creation.
    | When disabled, the system behaves exactly as before (no payment required).
    |
    */
    'payments_enabled' => env('PAYMENTS_ENABLED', false),
    
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    |
    | The payment gateway to use. Currently supported:
    | - 'stub': Mock payment for development/testing
    | - 'tap': Tap Payments (future)
    | - 'hyperpay': HyperPay (future)
    |
    */
    'payment_gateway' => env('PAYMENT_GATEWAY', 'stub'),
    
    /*
    |--------------------------------------------------------------------------
    | Emergency Department Payment Waiver
    |--------------------------------------------------------------------------
    |
    | When enabled, emergency department encounters automatically have
    | payment_status set to 'waived'.
    |
    */
    'emergency_dept_waives_payment' => env('EMERGENCY_DEPT_WAIVES_PAYMENT', true),
    
    /*
    |--------------------------------------------------------------------------
    | Auto-Assignment (Phase 3)
    |--------------------------------------------------------------------------
    */
    'auto_assignment_enabled' => env('AUTO_ASSIGNMENT_ENABLED', false),
];
