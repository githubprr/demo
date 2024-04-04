<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DistrictMaster;
use App\TalukaMaster;
use Auth;
use File;
use Excel;

class TalukaMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('talukas','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $talukas = TalukaMaster::with('district')->withCount('villages')->get();
        return view('taluka_master.list', compact('talukas'));
    }
    public function add(){
        if(!$this->getPermission('talukas','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $districts = DistrictMaster::get();
    	return view('taluka_master.add', compact('districts'));
    }
    public function save(Request $request){
        if(!$this->getPermission('talukas','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $taluka = new TalukaMaster;
        $taluka->district_id  = $request->district_id;
        $taluka->name = $request->name;
        $taluka->name_hn = $request->name_hn;
        $taluka->name_mr = $request->name_mr;
        if($taluka->save())
           return redirect('/talukaMaster')->with('success','Taluka added successful');
    	else
           return redirect('/talukaMaster')->with('error','Taluka add failed');
    }
    public function edit($id){
        if(!$this->getPermission('talukas','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$taluka = TalukaMaster::Where('id',$id)->first();
        if($taluka) {
            $districts = DistrictMaster::get();
            return view('taluka_master.edit',compact('districts', 'taluka'));
        }
        return redirect('/talukaMaster')->with('error','Taluka not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('talukas','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$taluka = TalukaMaster::Where('id',$request->id)->first();
        if($taluka) {
            $taluka->district_id  = $request->district_id;
        	$taluka->name = $request->name;
            $taluka->name_hn = $request->name_hn;
            $taluka->name_mr = $request->name_mr;
        	if($taluka->save())
               return redirect('/talukaMaster')->with('success','Taluka updated successful');
        	else
               return redirect('/talukaMaster')->with('error','Taluka update failed');
        }
        return redirect('/talukaMaster')->with('error','Taluka not found');
    }
    public function delete($id){
        if(!$this->getPermission('talukas','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$taluka = TalukaMaster::Where('id',$id)->first();
    	if($taluka) {
        	if($taluka->delete())
               return redirect('/talukaMaster')->with('success','Taluka deleted successful');
        	else
               return redirect('/talukaMaster')->with('error','Taluka delete failed');
        }
        return redirect('/talukaMaster')->with('error','Taluka not found');
    }
    public function getTalukaByDistrict($id){
        $talukas = TalukaMaster::Where('district_id',$id)->get();
        return response()->json($talukas);
    }
    public function importTalukas(Request $request)
    {
        if(!$this->getPermission('talukas','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $request->validate([
            'import_file' => 'required'
        ]);

        $path = $request->file('import_file')->getRealPath();
        $data = Excel::load($path)->get();
        if($data->count()){
            $districts = DistrictMaster::get();
            foreach ($data as $key => $value) {
                $filtered_collection = $districts->filter(function ($item) use ($value) {
                    return $item->name==$value->district;
                })->first();
                if(isset($filtered_collection) && isset($filtered_collection->id)) {
                    $arr[] = [
                        'name' => $value->taluka,
                        'name_hn' => (isset($value->taluka_hn))?$value->taluka_hn:null,
                        'name_mr' => (isset($value->taluka_mr))?$value->taluka_mr:null,
                        'district_id' => $filtered_collection->id
                    ];
                }
            }

            if(!empty($arr)){
                TalukaMaster::insert($arr);
            }
        }

        return back()->with('success', 'Talukas import successfully.');
    }
    public function truncate()
    {
        TalukaMaster::query()->truncate();
        return back();
    }
}
