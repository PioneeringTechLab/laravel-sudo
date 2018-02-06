# Laravel Sudo

Composer package for Laravel that allows for the use of "sudo mode" in protected areas.

The mode will last for a pre-determined amount of time before either a re-prompt or the currently-authenticated user logs-out.

This package can also integrate with the [Laravel Directory Authentication](https://github.com/csun-metalab/laravel-directory-authentication) package if the configured `User` model is an instance or subclass of the `CSUNMetaLab\Authentication\MetaUser` class.

The aforementioned integration would check whether the current user is actually masquerading as someone else. That way, the password prompt will be for the *masquerading* user, not the *masqueraded* user.

## Table of Contents

* [Installation](#installation)
    * [Composer and Service Provider](#composer-and-service-provider)
    * [Route Installation](#route-installation)
        * [Laravel 5.1 and Above](#laravel-51-and-above)
        * [Laravel 5.0](#laravel-50)
    * [Middleware Installation](#middleware-installation)
    * [Publish Everything](#publish-everything)
* [Required Environment Variables](#required-environment-variables)
* [Optional Environment Variables](#optional-environment-variables)
* [Routing](#routing)
    * [Display Sudo Form](#display-sudo-form)
    * [Process Sudo Form](#process-sudo-form)
* [Middleware](#middleware)
    * [Sudo Middleware](#sudo-middleware)
    * [Enforcing Sudo Mode](#enforcing-sudo-mode)
* [Controller](#controller)
    * [Showing the Sudo Form](#showing-the-sudo-form)
    * [Processing the Sudo Form](#processing-the-sudo-form)
* [Custom Form Request](#custom-form-request)
    * [Sudo Form Request](#sudo-form-request)
* [Custom Messages](#custom-messages)
* [View](#view)
* [Resources](#resources)

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

### Middleware Installation

Add the middleware to your `$routeMiddleware` array in `app/Http/Kernel.php` to enable it to protect routes:

```
protected $routeMiddleware = [
   //...

   'sudo' => \CSUNMetaLab\Sudo\Http\Middleware\Sudo::class,

   // You can also use this based on Laravel convention:
   // 'sudo' => 'CSUNMetaLab\Sudo\Http\Middleware\Sudo',

   //...
];
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

There are currently no required environment variables but there is an [optional environment variable](#optional-environment-variables).

## Optional Environment Variables

### SUDO_DURATION

The time (in seconds) that "sudo mode" is active for the existing authenticated user before a reprompt.

Default is two hours (7200 seconds).

## Routing

In all cases for the routes exposed by the package, you are free to modify the path of the route but keep these two constraints in mind:

1. Please **do not** modify the HTTP method of the routes unless you are also planning to modify the published view.
2. Please **do not** modify the route names since both the underlying controller functionality as well as the published view use them.

### Display Sudo Form

* Path: `/sudo`
* HTTP method: `GET`
* Route name: `sudo.create`

### Process Sudo Form

* Path: `/sudo`
* HTTP method: `POST`
* Route name: `sudo.store`

## Middleware

TBD

### Sudo Middleware

TBD

### Enforcing Sudo Mode

TBD

## Controller

TBD

### Showing the Sudo Form

TBD

### Processing the Sudo Form

TBD

## Custom Form Request

The controller leverages a custom form request class in order to accept and process the input. The form request exposes custom validation rules and error messages.

### Sudo Form Request

This class is namespaced as `CSUNMetaLab\Sudo\Http\Requests\SudoFormRequest`.

Most of the data required for processing will be added by the matching controller so there are not many validation rules for this request.

#### Validation Rules

`password.required`: the `password` field must have a non-null value in the request

#### Validation Messages

`sudo.errors.v.password.required`: the `password` field has no input

## Custom Messages

The custom messages for this package can be found in `resources/lang/en/sudo.php` by default. The messages can also be overridden as needed.

You may also translate the messages in that file to other languages to promote localization, as well.

The package reads from this file (using the configured localization) for all messages it must display to the user or write to any logs.

## View

TBD

## Resources

### Middleware

* [Middleware in Laravel 5.0](https://laravel.com/docs/5.0/middleware)
* [Middleware in Laravel 5.1](https://laravel.com/docs/5.1/middleware)
* [Middleware in Laravel 5.2](https://laravel.com/docs/5.2/middleware)
* [Middleware in Laravel 5.3](https://laravel.com/docs/5.3/middleware)
* [Middleware in Laravel 5.4](https://laravel.com/docs/5.4/middleware)
* [Middleware in Laravel 5.5](https://laravel.com/docs/5.5/middleware)