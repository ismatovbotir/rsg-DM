<?php

/*
| Local development shortcut: emulate authorization so the login screen can be skipped.
| Active only when APP_ENV=local AND DEV_AUTOLOGIN=true. Never enable in production.
| Use /dev/login/admin or /dev/login/user to switch the emulated user.
*/
return [
    'autologin' => (bool) env('DEV_AUTOLOGIN', false),

    // Show the dev quick-login buttons on the login page without auto-login (local env only).
    'quick' => (bool) env('DEV_QUICKLOGIN', false),

    // Emulated accounts (created by DatabaseSeeder).
    'users' => [
        'admin' => 'admin@rsg.uz',
        'user' => 'pda@rsg.uz',
    ],

    // Signed in automatically when nobody is logged in.
    'default' => env('DEV_USER', 'user'),
];
