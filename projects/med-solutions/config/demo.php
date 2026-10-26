<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quick Role Switcher (Demo / Preview Only)
    |--------------------------------------------------------------------------
    | Lets a visitor instantly sign in as the seeded demo Admin, Staff or
    | Patient account from the header. It bypasses the password form, so it
    | must stay disabled (DEMO_ROLE_SWITCHER=false) on any real deployment.
    | The controller also refuses to run when APP_ENV=production.
    */
    'role_switcher' => (bool) env('DEMO_ROLE_SWITCHER', false),
];
