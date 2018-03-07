# Laravel Sudo

Composer package for Laravel 5.0 and above that allows for the use of "sudo mode" in protected areas. "Sudo mode" refers to the requirement that a user must re-enter his password before performing certain actions and will not have to re-enter it for a certain amount of time, similar to how the [sudo utility](https://en.wikipedia.org/wiki/Sudo) functions on *nix-based operating systems.

The mode will last for a pre-determined amount of time before either a re-prompt or the currently-authenticated user logs-out.

This package can also integrate with the [Laravel Directory Authentication](https://github.com/csun-metalab/laravel-directory-authentication) package if the configured `User` model is an instance or subclass of the `CSUNMetaLab\Authentication\MetaUser` class.

The aforementioned integration would check whether the current user is actually masquerading as someone else. That way, the password prompt will be for the *masquerading* user, not the *masqueraded* user.

## Table of Contents

* [Installation](#installation)
    * [Composer and Service Provider](#composer-and-service-provider)
    * [Middleware Installation](#middleware-installation)
    * [Publish Everything](#publish-everything)
* [Required Environment Variables](#required-environment-variables)
* [Optional Environment Variables](#optional-environment-variables)
* [Middleware](#middleware)
    * [Enforcing Sudo Mode](#enforcing-sudo-mode)
    * [Sudo Middleware](#sudo-middleware)
* [Routes and Controller](#routes-and-controller)
    * [Routes](#routes)
    * [Controller](#controller)
* [Custom Messages](#custom-messages)
    * [Validation Messages](#validation-messages)
    * [Authentication Messages](#authentication-messages)
* [Helper Methods](#helper-methods)
    * [Check Sudo Mode](#check-sudo-mode)
    * [Generate Previous Input Markup](#generate-previous-input-markup)
* [View](#view)
    * [Hidden Input Metadata](#hidden-input-metadata)
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
* Messages (tagged as `lang`) - these go into your `resources/lang/en` directory as `sudo.php`
* Views (tagged as `views`) - these go into your `resources/views/vendor/sudo` directory

## Required Environment Variables

There are currently no required environment variables but there are [optional environment variables](#optional-environment-variables).

## Optional Environment Variables

### SUDO_DURATION

The time (in minutes) that "sudo mode" is active for the existing authenticated user before a reprompt. This time will reset if he logs-out before the duration has been exhausted.

Default is `120` (two hours).

### SUDO_USERNAME

The attribute in your configured `User` model that represents the username by which an individual authenticates.

Default is `email`.

### SUDO_AUTH_USERNAME_KEY

The username key used in the call to `Auth::attempt()` when trying the provided user credentials. This is typically the first key in the array that gets passed to `Auth::attempt()`.

This is modifiable in order to allow the package to conform to your custom auth driver.

Default is `username`.

### SUDO_AUTH_PASSWORD_KEY

The password key used in the call to `Auth::attempt()` when trying the provided user credentials. This is typically the second key in the array that gets passed to `Auth::attempt()`.

This is modifiable in order to allow the package to conform to your custom auth driver.

Default is `password`.

### SUDO_PROMPT_ONLY_WHILE_MASQUERADING

This value only matters when used in conjunction with the Composer package [csun-metalab/laravel-directory-authentication](https://github.com/csun-metalab/laravel-directory-authentication) to promote further integration between the two packages.

If this value is set to `true` then the user will only be re-prompted for his password if he is masquerading as someone else. If not, he will **NOT** be re-prompted; he will be able to perform the "sudo mode" actions **without** having to re-enter his password.

Default is `false`.

## Middleware

This package is driven primarily by a single middleware class though it contains a considerable amount of functionality and decision-making.

### Enforcing Sudo Mode

In order to enforce "sudo mode" you would need to protect a set of routes with both the `auth` and `sudo` middleware as shown here:

```
Route::group(['middleware' => ['auth', 'sudo']], function () {
  Route::post('secret_data', 'SomeController@postSecret');
  Route::post('admin_data', 'SomeController@postAdmin');
  Route::delete('remove_user', 'SomeController@removeUser');
});
```

This is just an example, of course, but the above route group would first ensure that the individual is authenticated before attempting to access the sections. If the individual is authenticated, they would then be greeted with a password re-prompt if "sudo mode" is not currently active based upon the criteria set forth in the [Sudo Criteria](#sudo-criteria) section.

**NOTE**: Please **do not** protect routes with a `GET` method using the `sudo` middleware as the request is re-sent using the original HTTP method and data (and hence the password would become part of the URL in a `GET` submission). Use some kind of role authorization check or policy on `GET` routes instead.

### Sudo Middleware

The middlware class is namespaced as `CSUNMetaLab\Sudo\Http\Middleware\Sudo`.

#### Sudo Criteria

In order for the currently-authenticated user to be shown the password re-prompt, one of the following criteria must be met:

1. The user is entering a "sudo mode" section for the first time in the session
2. The length of time described by the [SUDO_DURATION](#sudo-duration) environment variable has passed since the user last entered his password to enter "sudo mode"
3. The [SUDO_PROMPT_ONLY_WHILE_MASQUERADING](#sudo-prompt-only-while-masquerading) environment variable has been set to `true` and its matching criteria has been met
4. The user attempted to enter "sudo mode" and the re-authentication attempt failed due to an incorrect password

#### Sudo Functionality

The functionality of the middleware is fairly in-depth and takes several cases into account.

The steps are as follows:

1. If the attempting to enter "sudo mode" has not authenticated (such as when a route has not been protected with the `auth` middleware) he is returned back to his previous location with an error message (`errors.a.user.invalid`)
2. If the currently-authenticated user model is an instance or subclass of `CSUNMetaLab\Authentication\MetaUser` then the following checks are performed:
    1. The user instance is checked for a masquerading user
    2. The configuration is checked for whether the password re-prompt should occur only for masquerading users
    3. If the user is not masquerading the request proceeds to the next point in the request pipeline and the prompt is ignored
    4. If the user is masquerading then the the rest of the checks for the middleware proceed
3. A decision is made whether to show the password re-prompt based on either criteria 1 or 2 in [Sudo Criteria](#sudo-criteria)
4. If the password re-prompt should be shown, the following steps are performed:
    1. The middleware checks to see whether a `sudo_password` value was included in the request
    2. If the value was either left out or is empty and the HTTP request method is not `GET`, an error is generated; otherwise, the middleware continues processing further
5. If the `sudo_password` request value has been included, the following steps are performed:
    1. If the user instance is an instance or subclass of `CSUNMetaLab\Authentication\MetaUser` AND the user is masquerading, then the current user instance is swapped to be the *masquerading* (original) user, not the *masqueraded* (current) user.
    2. The username (based upon the [SUDO_USERNAME](#sudo-username) attribute in the user model) is retrieved from the model
    3. The username and re-prompted password credentials are checked with a call to `Auth::attempt()` to take any custom user providers into account without going straight to the database
    4. If the call to `Auth::attempt()` succeeds, the following steps are performed:
        1. The `sudo_last_time` session value is replaced with the current time
        2. The `sudo_active` session value is set to `true`
        3. If the user was masquerading, the current user instance to be reported by `Auth::user()` is swapped **back** to the *masqueraded* user and is no longer the *masquerading* user
    5. If the call to `Auth::attempt()` fails, the following steps are performed:
        1. The flag to show the password re-prompt is set to `true`
        2. An error message (`errors.a.password.invalid`) is generated due to an incorrect password
        3. If the user was masquerading, the current user instance is set back to the masqueraded user with a call to `Auth::login()` to allow the user to try again
        4. If the user was not masquerading, the current user instance is set with a call to `Auth::login()` to allow the user to try again
6. If the password re-prompt should be shown (`$flash_and_show == true`) then the following steps are performed:
    1. An array of previous request input is generated without the `sudo_password`, `_method`, and `_token` values for reasons in the [View](#view) section
    2. A string representing the previous request input is generated so it can be rendered on the view in the form
    3. A view response is generated containing the variables in the [View](#view) section
    4. The middleware terminates its execution
7. If the password re-prompt should not be shown ("sudo mode" has either been successfully-entered or is already active within the configured duration) then the request moves to its next point in the pipeline

## Routes and Controller

This section is entirely optional. It should only be used if you are intending on allowing people to exit "sudo mode" manually before the given time duration has been exhausted.

### Routes

#### Laravel 5.1 and above

Add the following to your `routes.php` or `routes/web.php` file depending on Laravel version to enable the route:

```
Route::get('exit_sudo', '\CSUNMetaLab\Sudo\Http\Controllers\SudoController@exitSudoMode')
   ->name('sudo.exit');
```

#### Laravel 5.0

Add the following group to your `routes.php` file to enable the route:

```
Route::get('exit_sudo', [
  'uses' => '\CSUNMetaLab\Sudo\Http\Controllers\SudoController@exitSudoMode',
  'as' => 'sudo.exit',
]);
```

### Controller

The controller is namespaced as `CSUNMetaLab\Sudo\Http\Controllers\SudoController`.

The single method, `exitSudoMode()`, merely drops the session values that control "sudo mode" being active and redirects the user back to their previous location.

## Custom Messages

The custom messages for this package can be found in `resources/lang/en/sudo.php` by default. The messages can also be overridden as needed.

You may also translate the messages in that file to other languages to promote localization, as well.

The package reads from this file (using the configured localization) for all messages it must display to the user or write to any logs.

### Validation Messages

* `errors.v.password.required` - error message raised when the `sudo_password` field is left empty on the view

### Authentication Messages

* `errors.a.user.invalid` - error message raised when a non-authenticated individual attempts to enter "sudo mode"
* `errors.a.password.invalid` - error message raised when the sudo password entered does not match the user's credentials

## Helper Methods

The helper methods are defined within the `helpers.php` file in this package.

### Check Sudo Mode

You may use the `isSudoModeActive()` method to determine whether "sudo mode" is active. You may wish to invoke this method throughout your application to display a banner, for example, across the top of the screen to inform the user that he is in super-user mode.

Your Blade code might look something like this:

```
@if(isSudoModeActive())
  <div class="alert alert-info">
    <p>You can currently perform super-user tasks.</p>
  </div>
@endif
```

If, however, you also have enabled everything from the [Routes and Controller](#routes-and-controller) section you may also give the user the option to exit "sudo mode" early:

```
@if(isSudoModeActive())
  <div class="alert alert-info">
    <p>
      You can currently perform super-user tasks. <a href="{{ route('sudo.exit') }}">Exit sudo mode</a> if you no longer need this access.
    </p>
  </div>
@endif
```

### Generate Previous Input Markup

You may use the `generatePreviousInputMarkup()` method to generate the input markup from the request that triggered entry into "sudo mode". The input elements will be rendered as hidden `<input>` elements and this method also has support for a deeply-nested input array.

You would typically pass the `$input` array available in the view to this method. The `sudo_password`, `_method`, and `_token` values would not be included, however. A new `_token` value will need to be placed into the form within the view since the CSRF token would have been re-generated and the old token would cause an instance of `VerifyCsrfTokenException` to be thrown if it was used.

Your Blade code might look something like this (keep Laravel version in mind when using the un-sanitized syntax):

```
{!! generatePreviousInputMarkup($input) !!}
```

You may, however, opt to use the pre-generated `$input_markup` variable that will be passed to the view instead. Then your Blade code might look like this (keep Laravel version in mind when using the un-sanitized syntax):

```
{!! $input_markup !!}
```

## View

The view that will be displayed exists as `sudo.blade.php` and is located in the `resources/views/vendor/sudo` directory. The following variables are exposed to the view by the middleware:

* `$sudo_errors` - associative array of any errors that have arisen during the password re-prompt
* `$request_method` - string representing the HTTP method used to access the requested resource
* `$request_url` - string representing the URL used to access the requested resource
* `$input` - associative array of request input from the resource that triggered the password re-prompt
* `$input_markup` - string representing the HTML markup of the input fields within `$input`
* `$form_method` - string representing what the value of a plain-HTML form's `method` attribute should be (either `GET` or `POST`)

This view stands on its own as a Bootstrap view but you are free to customize it as you wish. Please take special care, however, when modifying anything around or inside the opening and closing `<form>` tags since that drives the "sudo mode" functionality.

The previous request input values are rendered as hidden `<input>` elements that can be added immediately to the form by rendering the `$input_markup` string as HTML.

### Hidden Input Metadata

You will need to ensure that a hidden CSRF `_token` field exists in the form as well but that's as simple as adding the following in your Blade code:

```
<input type="hidden" name="_token" value="{{ csrf_token() }}" />
```

If your request was mapped to an HTTP method other than `GET` or `POST` you'll need an additional line to add the `_method` field as well:

```
<input type="hidden" name="_method" value="{{ $request_method }}" />
```

Finally, ensure that the following hidden `<input>` element exists as well since that tells the middleware whether the submission came from the "sudo mode" screen:

```
<input type="hidden" name="sudo_mode_submission" value="true" />
```

The provided `sudo.blade.php` view provides all of these hidden tags already.

## Resources

### Middleware

* [Middleware in Laravel 5.0](https://laravel.com/docs/5.0/middleware)
* [Middleware in Laravel 5.1](https://laravel.com/docs/5.1/middleware)
* [Middleware in Laravel 5.2](https://laravel.com/docs/5.2/middleware)
* [Middleware in Laravel 5.3](https://laravel.com/docs/5.3/middleware)
* [Middleware in Laravel 5.4](https://laravel.com/docs/5.4/middleware)
* [Middleware in Laravel 5.5](https://laravel.com/docs/5.5/middleware)

### Blade Templates

* [Templates in Laravel 5.0](https://laravel.com/docs/5.0/templates)
* [Blade in Laravel 5.1](https://laravel.com/docs/5.1/blade)
* [Blade in Laravel 5.2](https://laravel.com/docs/5.2/blade)
* [Blade in Laravel 5.3](https://laravel.com/docs/5.3/blade)
* [Blade in Laravel 5.4](https://laravel.com/docs/5.4/blade)
* [Blade in Laravel 5.5](https://laravel.com/docs/5.5/blade)