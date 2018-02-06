# Laravel Sudo

Composer package for Laravel that allows for the use of "sudo mode" in protected areas.

The mode will last for a pre-determined amount of time before either a re-prompt or the currently-authenticated user logs-out.

This package also integrates with the [Laravel Directory Authentication](https://github.com/csun-metalab/laravel-directory-authentication) package to check whether the current user is actually masquerading as someone else. That way, the password prompt will be for the *masquerading* user, not the *masqueraded* user.

## Table of Contents

* [Installation](#installation)
    * [Composer and Service Provider](#composer-and-service-provider)
    * [Route Installation](#route-installation)
        * [Laravel 5.1 and Above](#laravel-51-and-above)
        * [Laravel 5.0](#laravel-50)
    * [Publish Everything](#publish-everything)
* [Required Environment Variables](#required-environment-variables)
* [Optional Environment Variables](#optional-environment-variables)

## Installation

### Composer and Service Provider

#### Composer

To install from Composer, use the following command:

```
composer require csun-metalab/laravel-sudo
```

#### Service Provider

Add the service provider to your `providers` array in `config/app.php` in Laravel as follows:

```
'providers' => [
   //...

   CSUNMetaLab\Sudo\Providers\SudoServiceProvider::class,

   // You can also use this based on Laravel convention:
   // 'CSUNMetaLab\Sudo\Providers\SudoServiceProvider',

   //...
],
```

### Route Installation

You will now need to add the various routes for the package. They are named routes since you can then customize the route paths based upon your own application. The package will use the route names instead of the paths when performing operations.

#### Laravel 5.1 and Above

Add the following group to your `routes.php` or `routes/web.php` file depending on Laravel version to enable the routes:

```
Route::group(['middleware' => ['auth']], function () {
  Route::get('sudo', '\CSUNMetaLab\Sudo\Http\Controllers\SudoController@create')->name('sudo.create');
  Route::post('sudo', '\CSUNMetaLab\Sudo\Http\Controllers\SudoController@store')->name('sudo.store');
});
```

#### Laravel 5.0

Add the following group to your `routes.php` file to enable the routes:

```
Route::group(['middleware' => ['auth']], function () {
  Route::get('sudo', [
    'uses' => '\CSUNMetaLab\Sudo\Http\Controllers\SudoController@create',
    'as' => 'sudo.create',
  ]);
  Route::post('sudo', [
    'uses' => '\CSUNMetaLab\Sudo\Http\Controllers\SudoController@store',
    'as' => 'sudo.store',
  ]);
});
```

### Publish Everything

Finally, run the following Artisan command to publish everything:

```
php artisan vendor:publish
```

The following assets are published:

* Configuration (tagged as `config`) - these go into your `config` directory
* Messages (tagged as `lang`) - these go into your `resources/lang/en directory` as `sudo.php`
* Views (tagged as `views`) - these go into your `resources/views/vendor/sudo` directory

## Required Environment Variables

There are currently no required environment variables but there is an [optional environment variable](#optional-environment-variable).

## Optional Environment Variables

### SUDO_DURATION

The time (in seconds) that "sudo mode" is active for the existing authenticated user before a reprompt.

Default is two hours (7200 seconds).