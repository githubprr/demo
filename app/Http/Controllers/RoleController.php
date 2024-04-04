<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\File;
use App\User;
use Illuminate\Support\Facades\Redirect;
use DB;
use Auth;
use Response;


use App\UserRolesModel;

class RoleController extends Controller{

	public function __construct()
	{
		$this->middleware('auth');
	}

	public function index()
	{
		$userRole = Auth::user()->role;
		// if($userRole == 'admin')
		// {
			// return redirect('/sliderMaster');
		// }
		// else if($userRole == 'user')
		// {
			return redirect('/dashboard');
		// }
		// else
		// {
		// 	return view('error_page');
		// }
	}
}