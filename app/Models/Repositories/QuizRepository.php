<?php

namespace QuizSystem\Models\Repositories;

use DB;
use QuizSystem\Models\Entities\Quiz;
use QuizSystem\Models\Interfaces\IQuizRepository;

class QuizRepository implements IQuizRepository {

    public function getQuizList(
        $paginated=false, $format='array', $dataPerPage=0, $columns=['*'])
    {
        if($paginated === true) {
            return $this->getPaginatedQuizList($format, $dataPerPage, $columns);
        }
    }

    private function getPaginatedQuizList($format, $dataPerPage, $columns)
    {
        if($format === 'json') {
            return Quiz::withTrashed()
                        ->select($columns)
                        ->Paginate($dataPerPage)
                        ->toJson();
        }
    }

    public function createQuiz($paramsArray)
    {
        DB::beginTransaction();
        try {
            $user = Quiz::create([
                'name' => ucwords(strtolower($paramsArray['name'])),
                'description' => $paramsArray['description'],
                'timed' => $paramsArray['timed'],
                'no_of_questions' => intval($paramsArray['no_of_questions']),
                'user_retries' => intval($paramsArray['user_retries']),
                'active_status' => 1
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
        DB::commit();
        return true;
    }

    // to use any function of Eloquent class directly
    public function __call($method, $args)
    {
        return call_user_func_array([$this->user, $method], $args);
    }


}