<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\PointMaster;
use Auth;
use File;

class PointMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('points','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $points = PointMaster::get();
        return view('point_master.list', compact('points'));
    }
    public function add(){
        if(!$this->getPermission('points','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        return view('point_master.add');
    }
    public function save(Request $request){
        if(!$this->getPermission('points','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $point = new PointMaster;
        $point->slug = $request->slug;
        $point->point = $request->point;
        $point->type = $request->type;
        if($point->save())
           return redirect('/pointMaster')->with('success','Point added successful');
        else
           return redirect('/pointMaster')->with('error','Point add failed');
    }
    public function edit($id){
        if(!$this->getPermission('points','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $point = PointMaster::Where('id',$id)->first();
        if($point) {
            return view('point_master.edit',compact('point'));
        }
        return redirect('/pointMaster')->with('error','Point not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('points','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $point = PointMaster::Where('id',$request->id)->first();
        if($point) {
            $point->point = $request->point;
            $point->type = $request->type;
            if($point->save())
               return redirect('/pointMaster')->with('success','Point updated successful');
            else
               return redirect('/pointMaster')->with('error','Point update failed');
        }
        return redirect('/pointMaster')->with('error','Point not found');
    }
    public function delete($id){
        if(!$this->getPermission('points','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $point = PointMaster::Where('id',$id)->first();
        if($point) {
            if($point->delete())
               return redirect('/pointMaster')->with('success','Point deleted successful');
            else
               return redirect('/pointMaster')->with('error','Point delete failed');
        }
        return redirect('/pointMaster')->with('error','Point not found');
    }
    public function apiGetPoints()
    {
        $points = PointMaster::get();
        if($points) {
            return response()->json([
                'errorCode' => 0,
                'data' => $points,
                'message' => 'Get points succesfully'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get points failed'
        ]);
    }
}
