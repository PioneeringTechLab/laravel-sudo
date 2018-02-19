<?php

namespace CSUNMetaLab\Sudo\Http\Middleware;

use Auth;
use Closure;
use Carbon\Carbon;

class Sudo
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $show_sudo = $this->shouldShowSudo($request);
        $sudo_username = config('sudo.username');

        $flash_and_show = false; // true to flash input and show sudo form
        $sudoErrors = [];

        // display the sudo view with flashed input data if it matches the
        // criteria specified by shouldShowSudo() or if there was an auth
        // error
        if($show_sudo) {
            $flash_and_show = true;

            // if we are processing the sudo form, check to see whether a
            // password was supplied since we will need to add an error if
            // that case has not been met
            $pw = $request->input('sudo_password');
            if(empty($pw) && !$request->isMethod('get')) {
                $sudoErrors['password'] = trans('sudo.errors.v.password.required');
            }
        }
        else if($request->has('sudo_password')) {
            // TODO: this will have to be modified to take a masqueraded user
            // into account if using a subclass of MetaUser
            $user = Auth::user();

            // show the sudo view if the credentials do not match
            $creds = [
                $sudo_username => $user->$sudo_username,
                'password' => $request->input('sudo_password')
            ];
            if(Auth::attempt($creds)) {
                // password matches, so enable sudo mode and set the last sudo
                // time for future time checks
                $time = Carbon::now()->toDateTimeString(); // Y-m-d H:i:s
                session(['sudo_last_time' => $time]);
            }
            else
            {
                $flash_and_show = true;
                $sudoErrors['password'] = trans('sudo.errors.a.password.invalid');

                // even though the authentication attempt failed, make the user
                // instance active to allow for them to try again
                Auth::login($user);
            }
        }

        // if we should flash the input and show the view, do it
        if($flash_and_show) {
            $request->flash();
            return view('sudo::sudo', compact('sudoErrors'));
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
        // does not exist or the maximum duration has passed
        $sudo_last_time = session('sudo_last_time');
        if(empty($sudo_last_time)) {
            return true;
        }
        else if(Carbon::now()->diffInMinutes
            (Carbon::createFromFormat('Y-m-d H:i:s', $sudo_last_time)) > config('sudo.duration')) {
            return true;
        }

        return false;
    }
}
