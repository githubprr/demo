<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\StateMaster;
use App\DistrictMaster;
use Auth;
use File;
use Excel;

class DistrictMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('districts','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $districts = DistrictMaster::with('state')->withCount('talukas')->get();
        return view('district_master.list', compact('districts'));
    }
    public function add(){
        if(!$this->getPermission('districts','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $states = StateMaster::get();
    	return view('district_master.add', compact('states'));
    }
    public function save(Request $request){
        if(!$this->getPermission('districts','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $district = new DistrictMaster;
        $district->state_id  = $request->state_id;
        $district->name = $request->name;
        $district->name_hn = $request->name_hn;
        $district->name_mr = $request->name_mr;
        if($district->save())
           return redirect('/districtMaster')->with('success','District added successful');
    	else
           return redirect('/districtMaster')->with('error','District add failed');
    }
    public function edit($id){
        if(!$this->getPermission('districts','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$district = DistrictMaster::Where('id',$id)->first();
    	if($district) {
            $states = StateMaster::get();
            return view('district_master.edit',compact('states', 'district'));
        }
        return redirect('/districtMaster')->with('error','District not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('districts','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$district = DistrictMaster::Where('id',$request->id)->first();
        if($district) {
            $district->state_id  = $request->state_id;
        	$district->name = $request->name;
            $district->name_hn = $request->name_hn;
            $district->name_mr = $request->name_mr;
        	if($district->save())
               return redirect('/districtMaster')->with('success','District updated successful');
        	else
               return redirect('/districtMaster')->with('error','District update failed');
        }
        return redirect('/districtMaster')->with('error','District not found');
    }
    public function delete($id){
        if(!$this->getPermission('districts','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$district = DistrictMaster::Where('id',$id)->first();
    	if($district) {
            if($district->delete())
               return redirect('/districtMaster')->with('success','District deleted successful');
        	else
               return redirect('/districtMaster')->with('error','District delete failed');
        }
        return redirect('/districtMaster')->with('error','District not found');
    }
    public function getDistrictByState($id){
        $districts = DistrictMaster::Where('state_id',$id)->get();
        return response()->json($districts);
    }
    public function importDistricts(Request $request)
    {
        if(!$this->getPermission('districts','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $request->validate([
            'import_file' => 'required'
        ]);

        $path = $request->file('import_file')->getRealPath();
        $data = Excel::load($path)->get();
        if($data->count()){
            $states = StateMaster::get();
            foreach ($data as $key => $value) {
                $filtered_collection = $states->filter(function ($item) use ($value) {
                    return $item->name==$value->state;
                })->first();
                if(isset($filtered_collection) && isset($filtered_collection->id)) {
                    $arr[] = [
                        'name' => $value->district,
                        'name_hn' => (isset($value->district_hn))?$value->district_hn:null,
                        'name_mr' => (isset($value->district_mr))?$value->district_mr:null,
                        'state_id' => $filtered_collection->id
                    ];
                }
            }

            if(!empty($arr)){
                DistrictMaster::insert($arr);
            }
        }

        return back()->with('success', 'Districts import successfully.');
    }
    public function truncate()
    {
        DistrictMaster::query()->truncate();
        return back();
    }
}
