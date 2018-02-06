# Laravel Sudo

Composer package for Laravel that allows for the use of "sudo mode" in protected areas.

The mode will last for a pre-determined amount of time before either a re-prompt or the currently-authenticated user logs-out.

This package also integrates with the [Laravel Directory Authentication](https://github.com/csun-metalab/laravel-directory-authentication) package to check whether the current user is actually masquerading as someone else. That way, the password prompt will be for the *masquerading* user, not the *masqueraded* user.

## Table of Contents

TBD