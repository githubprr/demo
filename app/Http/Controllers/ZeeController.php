<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ZeeUsers;
use DB;
use Auth;
use File;

class ZeeController extends Controller
{
    public function index($id){
        $user = ZeeUsers::where('id',$id)->first();
        $user->wallet += 10;
        $user->save();
        if($user && isset($user->referred_by)) {
            $this->referral_bonus($user->referred_by,6,60);
        }
        dd($user);
        return '';
    }
    public function referral_bonus($id,$cnt,$amt){
        $user = ZeeUsers::where('id',$id)->first();
        if($user) {
            $user->wallet += $amt;
            $user->save();
            $this->referral_bonus($user->referred_by,$cnt-1,$amt-10);
        }
        return false;
    }
}
