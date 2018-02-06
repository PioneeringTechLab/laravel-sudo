<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Sudo mode duration before password re-prompt
    |--------------------------------------------------------------------------
    |
    | The time (in minutes) that "sudo mode" is active for the existing
    | authenticated user before a reprompt.
    |
    | Default is 120 minutes (two hours).
    |
    */
    'duration' => env("SUDO_DURATION", 120),

];