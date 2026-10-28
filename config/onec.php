<?php

/*
| Adines exchange. Leave ONEC_BASE_URL empty to disable sync (documents stay "pending").
| Assumed Adines HTTP service contract (adjust OneCClient if yours differs):
|   POST {base}/documents  <- JSON document with lines
*/
return [
    'base_url' => env('ONEC_BASE_URL'),
    'user' => env('ONEC_USER'),
    'password' => env('ONEC_PASSWORD'),
    'timeout' => (int) env('ONEC_TIMEOUT', 20),
    // Inbound API (/api/v1/sync/*, /api/shop...) is open while this is empty; set it to require "Authorization: Bearer <token>".
    'api_token' => env('ONEC_API_TOKEN'),

];
