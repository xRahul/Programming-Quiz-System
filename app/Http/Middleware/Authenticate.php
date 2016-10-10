<?php

namespace QuizSystem\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;

class Authenticate
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  Guard  $auth
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        // check whether user is logged in or not
        if (!$this->auth->check()) {
            flash()->info('Auth!', 'You are not logged in.');
            return redirect()->route('login.get');
        }

        // check whether any role would be acceptable
        if ($role == 'all') {
            return $next($request);
        }

        // check if user is a guest or doesn't have the required role
        if ($this->auth->guest() || !$this->auth->user()->hasRole($role)) {
            flash()->error('Authentication Failed', 'You are not privileged.');
            return redirect()->back()->withInput();
        }

        // take user to his request if all 3 condition fails
        return $next($request);
    }
}
