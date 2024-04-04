<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\QuizMaster;

class QuizController extends Controller
{
    public function randomQuizs($type)
    {
        $quizs = QuizMaster::where('type',$type)->inRandomOrder()->limit(5)->get();
        if(count($quizs)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $quizs,
                'message' => 'Get quizs successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get quizs failed'
        ]);
    }
}
