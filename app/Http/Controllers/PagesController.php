<?php

namespace QuizSystem\Http\Controllers;

use Illuminate\Http\Request;

use QuizSystem\Http\Requests;
use QuizSystem\Http\Controllers\Controller;

class PagesController extends Controller
{
    public function homepage()
    {
        return view('controllers.pages.homepage');
    }

    public function adminpage()
    {
    	return view('controllers.pages.adminpage');
    }
}
