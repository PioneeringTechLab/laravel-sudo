<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Sudo mode duration before password re-prompt
    |--------------------------------------------------------------------------
    |
    | The time (in seconds) that "sudo mode" is active for the existing
    | authenticated user before a reprompt.
    |
    | Default is two hours (7200 seconds).
    |
    */
    'duration' => env("SUDO_DURATION", 7200),

];