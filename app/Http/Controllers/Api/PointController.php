<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\PointHistory;
use Auth;

class PointController extends Controller
{
    public function pointsHistory()
    {
		$pointHistory = PointHistory::where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
		if(count($pointHistory)) {
            return response()->json([
                'errorCode' => 0,
                'data' => $pointHistory,
                'message' => 'Get point history successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Get point history failed'
        ]);
    }
}
