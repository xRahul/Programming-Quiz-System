<?php

namespace QuizSystem\Http\Controllers;

use Auth;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


    /**
     * redirect user to their default landing pages
     * @return redirect pages.adminpage, or, pages.homepage
     */
    protected function redirectToDefaultUserPage()
    {
    	if(Auth::check() && Auth::user()->hasRole('admin')) {
            return redirect()->route('pages.adminpage');
        } else {
            return redirect()->route('pages.homepage');
        }
    }
}
