<?php

return [
    'force_log_delivery' => (bool) env('COMMUNICATION_FORCE_LOG_DELIVERY', false),
    'sms_provider' => env('COMMUNICATION_SMS_PROVIDER', 'log'),
    'sms_endpoint' => env('COMMUNICATION_SMS_ENDPOINT'),
    'slack_webhook' => env('COMMUNICATION_SLACK_WEBHOOK'),
];
