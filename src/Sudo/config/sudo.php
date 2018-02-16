<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Sudo mode duration before password re-prompt
    |--------------------------------------------------------------------------
    |
    | The time (in minutes) that "sudo mode" is active for the existing
    | authenticated user before a reprompt. This time will reset if he logs-out
    | before the duration has been exhausted.
    |
    | Default is 120 minutes (two hours).
    |
    */
    'duration' => env("SUDO_DURATION", 120),

    /*
    |--------------------------------------------------------------------------
    | Sudo mode authentication username
    |--------------------------------------------------------------------------
    |
    | The attribute in your configured User model that represents the username
    | by which an individual authenticates.
    |
    | Default is "email".
    |
    */
    'username' => env("SUDO_USERNAME", "email"),

];