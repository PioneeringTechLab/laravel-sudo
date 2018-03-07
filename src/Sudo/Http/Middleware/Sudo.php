<?php

namespace CSUNMetaLab\Sudo\Http\Middleware;

use Auth;
use Closure;
use Carbon\Carbon;
use Log;

class Sudo
{
    /**
     * Handle an incoming request. You may wish to read the documentation on
     * the steps performed by this middleware as well.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @see https://github.com/csun-metalab/laravel-sudo/blob/dev/README.md
     */
    public function handle($request, Closure $next)
    {
        // grab the authenticated user or redirect back to the previous
        // location if there is no user since "sudo mode" REQUIRES an
        // active user instance
        $user = Auth::user();
        if(empty($user)) {
            return redirect()->back()->withErrors([
                'auth' => trans('sudo.errors.a.user.invalid'),
            ]);
        }

        // determine first whether the re-prompt should be shown and processed
        // ONLY if an individual is masquerading
        $is_meta_user = is_a($user, "CSUNMetaLab\Authentication\MetaUser");
        $is_masquerading = false;
        if($is_meta_user) {
            $is_masquerading = $user->isMasquerading();
            if(config('sudo.prompt_only_while_masquerading')) {
                if(!$is_meta_user || !$is_masquerading) {
                    // proceed to the next request in the pipeline since we are
                    // either not a MetaUser subclass or we are not masquerading
                    return $next($request);
                }
            }
        }

        // determine whether the sudo screen should be shown based upon active
        // status or passed duration
        $show_sudo = $this->shouldShowSudo($request);
        $sudo_username = config('sudo.username');

        // retrieve request data and generate the form request method
        $request_method = strtoupper($request->method());
        $request_url = $this->generateRequestUrl($request);
        $form_method = ($request_method == "GET" ? "GET" : "POST");

        $flash_and_show = false; // true to flash input and show sudo form
        $sudo_errors = []; // key-value pair of any error messages that arise

        // display the sudo view with flashed input data if it matches the
        // criteria specified by shouldShowSudo() or if there was an auth
        // error
        if($show_sudo) {
            $flash_and_show = true;

            // if we are processing the sudo form, check to see whether a
            // password was supplied since we will need to add an error if
            // that case has not been met
            /*$pw = $request->input('sudo_password');
            dd($request_method);
            if(empty($pw) && $request_method != 'GET') {
                $sudo_errors['password'] = trans('sudo.errors.v.password.required');
            }*/
        }
        if($request->has('sudo_password')) {
            // integration with the csun-metalab/laravel-directory-authentication
            // package requires a check on whether the User model instance is
            // an instance of the base MetaUser class so we will store the
            // existing user as $masqueraded_user just in case
            $masqueraded_user = $user;

            // we have to take into account whether the currently-authenticated
            // user according to Auth::user() is actually an account that is
            // being masqueraded and NOT the original user that logged-in.
            if($is_meta_user && $is_masquerading) {
                // replace the $user instance with the masquerading user
                // so the password will be checked on that user, not the
                // masqueraded user
                $user = $user->getMasqueradingUser();
            }

            // if the credentials match, then set the session value; otherwise,
            // show the sudo view since the credentials do not match
            $creds = [
                $sudo_username => $user->$sudo_username,
                'password' => $request->input('sudo_password')
            ];
            if(Auth::attempt($creds)) {
                // password matches, so enable sudo mode and set the last sudo
                // time for future time checks
                $time = Carbon::now()->toDateTimeString(); // Y-m-d H:i:s
                session(['sudo_last_time' => $time, 'sudo_active' => true]);

                // if we were masquerading, then switch back to the masqueraded
                // user instead of the masquerading user
                if($is_masquerading) {
                    Auth::login($masqueraded_user);
                }
            }
            else
            {
                $flash_and_show = true;
                $sudo_errors['password'] = trans('sudo.errors.a.password.invalid');

                // even though the authentication attempt failed, make the user
                // instance active to allow for them to try again
                if($is_masquerading) {
                    Auth::login($masqueraded_user);
                }
                else
                {
                    Auth::login($user);
                }
            }
        }

        // if we should return the input and show the view, do it
        if($flash_and_show) {
            // drop out the "sudo_password", "_method" and CSRF token values
            // but return everything else for use
            $input = $request->except('sudo_password', '_method', '_token');
            $input_markup = generatePreviousInputMarkup($input);
            return response(view('sudo::sudo', compact(
                'sudo_errors', 'request_method', 'request_url', 'input',
                'input_markup', 'form_method'
            )));
        }
        
        return $next($request);
    }

    /**
     * Returns whether the sudo screen should be shown based upon either the
     * fact that it has not yet been shown or that the active duration has
     * passed.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    private function shouldShowSudo($request) {
        // only show the sudo view if the sudo_last_time session value either
        // does not exist, the sudo_active value does not exist, or the maximum
        // duration has passed
        $sudo_last_time = session('sudo_last_time');
        $sudo_active = session('sudo_active');
        if(empty($sudo_last_time) || empty($sudo_active)) {
            session(['sudo_active' => false]);
            return true;
        }
        else if(Carbon::now()->diffInMinutes
            (Carbon::createFromFormat('Y-m-d H:i:s', $sudo_last_time)) > config('sudo.duration')) {
            session(['sudo_active' => false]);
            return true;
        }

        return false;
    }

    /**
     * Generates the URL of the request that triggered the "sudo mode" entry.
     * We generate the request URL in this specific way since we want to allow
     * applications to use the Composer package csun-metalab/laravel-proxypass
     * and still work seamlessly.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    private function generateRequestUrl($request) {
        $request_path = $request->path();
        $query_string = $request->server('QUERY_STRING');
        if(!empty($query_string)) {
            $request_path .= "?{$query_string}";
        }
        
        return url($request_path);
    }
}
