<?php

namespace QuizSystem\Http\Controllers\Auth;

use Auth;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\ThrottlesLogins;

use QuizSystem\Http\Controllers\Controller;
use QuizSystem\Models\Interfaces\IUserRepository;

use QuizSystem\Http\Requests\LoginRequest;
use QuizSystem\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use ThrottlesLogins;

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct(IUserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    public function getRegister()
    {
        return view('auth.register');
    }


    public function getLogin(Request $request)
    {
        return view('auth.login');
    }


    public function postLogin(LoginRequest $request)
    {
        // logout the current user if any is logged in
        if (Auth::check()) {
            Auth::logout();
        }

        // user is successfully authenticated
        if (Auth::attempt([
                'email'     => strtolower($request->email),
                'password'  => $request->password
            ], $request->remember == 1 ? true : false)
        ) {
            flash()->success(
                'Success', 
                'You have logged in successfully!'
            );

            // redirect user according to their roles
            if(Auth::user()->hasRole('admin')) {
                return redirect()->intended(route('adminpage'));
            } else {
                return redirect()->intended(route('homepage'));
            }
        }

        // user fails authentication
        flash()->error(
            'Login Failed!', 
            'Please check your login details again'
        );
        return redirect()->back()->withInput();
    }

    public function getLogout()
    {
        if (!Auth::check()) {
            flash()->error('Logout Failed!', 'You are not logged in.');
            return redirect()->route('login.get');
        }

        Auth::logout();

        flash()->success('Success', 'You have logged out successfully');
        return redirect()->back();
    }

    
}
