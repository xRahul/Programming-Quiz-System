<?php

namespace QuizSystem\Http\Controllers;

use Illuminate\Http\Request;

use QuizSystem\Http\Requests;
use QuizSystem\Http\Controllers\Controller;
use QuizSystem\Models\Interfaces\IQuizRepository;


class QuizzesController extends Controller
{

    public function __construct(IQuizRepository $quizRepository)
    {
        $this->quizRepository = $quizRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $quizzes = $this->quizRepository
            ->getQuizList(true, 'json', 10, [
                'id', 'slug', 'name', 'timed', 'active_status'
            ]);
        // $quizzes = json_decode(json_encode($quizzes), true);
        // $quizzes = array_slice($quizzes, 6);
        return response()->json(json_decode($quizzes));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return response()->json(["Yo" => "Howdy"]);
    }
}
