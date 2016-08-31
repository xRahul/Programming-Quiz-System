<?php

namespace QuizSystem\Http\Controllers\Auth;

use Auth;

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

    /**
     * GET register user page
     * @return view auth.register
     */
    public function getRegister()
    {
        return view('auth.register');
    }

    /**
     * submit POST request to register user
     * @param  RegisterRequest $request validated register parameters
     * @return redirect back if failed, or to defaultUserpage
     */
    public function postRegister(RegisterRequest $request)
    {
        // convert request parameters to array
        $requestArray = $request->all();

        // set registering user to non admin if user is 
        // not being regestered via an admin
        if (! (Auth::check() && Auth::user()->hasRole('admin')) ) {
            $requestArray['admin_user'] = 0;
        }

        // create the user
        $userCreated = $this->userRepository
                            ->createUser($requestArray);

        // check if user has been created
        if($userCreated) {
            flash()->success(
                'Success', 
                "Account for "
                    .$requestArray['email']
                    ." has been created successfully!"
            );
            return $this->redirectToDefaultUserPage();
        }

        flash()->error(
                'Failed', 
                "Account wasn't created!"
            );
        return redirect()->back()->withInput();
    }

    /**
     * GET login page
     * @return view auth.login
     */
    public function getLogin()
    {
        return view('auth.login');
    }

    /**
     * submit POST request to login a user
     * @param  LoginRequest $request Validated login parameters
     * @return redirect back if failed, or to defaultUserpage 
     */
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
            return $this->redirectToDefaultUserPage();
        }

        // user fails authentication
        flash()->error(
            'Login Failed!', 
            'Please check your login details again'
        );
        return redirect()->back()->withInput();
    }

    /**
     * GET request to logout a user
     * @return redirect login page is not logged in, or
     *                  back after logging out
     */
    public function getLogout()
    {
        // check if user's not logged in
        if (!Auth::check()) {
            flash()->error('Logout Failed!', 'You are not logged in.');
            return redirect()->route('login.get');
        }

        // logout the user
        Auth::logout();

        flash()->success('Success', 'You have logged out successfully');
        return redirect()->back();
    }
}
