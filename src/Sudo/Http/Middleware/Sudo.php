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
        $sudo_username = config('sudo.username');

        // only show the sudo view if the sudo_last_time session value either
        // does not exist or is after the desired duration
        $sudo_last_time = session('sudo_last_time');
        $show_sudo = false;
        if(empty($sudo_last_time)) {
            $show_sudo = true;
        }
        else if(Carbon::now()->diffInMinutes
            (Carbon::createFromFormat('Y-m-d H:i:s', $sudo_last_time)) > config('sudo.duration')) {
            $show_sudo = true;
        }
        else if($request->has('sudo_password')) {
            // TODO: this will have to be modified to take a masqueraded user
            // into account if using a subclass if MetaUser

            // show the sudo view if the credentials do not match
            $creds = [
                $sudo_username => Auth::user()->$sudo_username,
                'password' => $request->input('sudo_password')
            ];
            if(!Auth::attempt($creds)) {
                $show_sudo = true;
            }
        }

        // display the sudo view with flashed input data
        if($show_sudo) {
            $request->flash();
            return view('sudo::sudo');
        }
        
        return $next($request);
    }
}
