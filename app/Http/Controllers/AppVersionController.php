<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AppVersion;

class AppVersionController extends Controller
{
    public function index(){
    	$appVersion = AppVersion::select('version')->first();
        if($appVersion) {
            return response()->json([
                'errorCode' => 0,
                'data' => $appVersion,
                'message' => 'Get app version successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get app version failed'
        ]);
    }
}