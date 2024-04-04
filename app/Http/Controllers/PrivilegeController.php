<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Privilege;
use App\Module;
use App\PrivilegeRoles;
use Auth;
use File;
use App\User;

class PrivilegeController extends Controller
{
    public function index(){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $privileges = Privilege::where('id','<>',1)->withCount('users')->get();
    	return view('privilege.list', compact('privileges'));
    }
    public function add(){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $modules = Module::get();
        return view('privilege.add', compact('modules'));
    }
    public function save(Request $request){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // dd($request->privileges);
        $privilege = new Privilege;
        $privilege->name = $request->name;
        if($privilege->save()) {
           foreach ($request->privileges as $key => $value) {
                $privilege_data = PrivilegeRoles::firstOrCreate([
                    'privilege_id' => $privilege->id, 'module_id' => $key],[
                    'is_visible' => (isset($value['is_visible']))?$value['is_visible']:0,
                    'is_create' => (isset($value['is_create']))?$value['is_create']:0,
                    'is_read' => (isset($value['is_read']))?$value['is_read']:0,
                    'is_edit' => (isset($value['is_edit']))?$value['is_edit']:0,
                    'is_delete' => (isset($value['is_delete']))?$value['is_delete']:0
                ]);
           }
           return redirect('/privileges')->with('success','Privilege added successful');
    	}
        else
           return redirect('/privileges')->with('error','Privilege add failed');
    }
    public function edit($id){
        $privilege = Privilege::Where('id',$id)->first();
    	if($privilege) {
        	$privilege_roles_data = PrivilegeRoles::Where('privilege_id',$id)->get();
            $privilege_roles = array();
            foreach ($privilege_roles_data as $key => $value) {
                $privilege_roles[$value->module_id] = $value->toArray();
            }
            // dd($privilege_roles);

        	$modules = Module::get();
            // foreach ($modules as $key => $module) {
            //     // dd($privilege_roles);
            //     dd(array_key_exists($module->id,$privilege_roles));
            // }
            return view('privilege.edit',compact('privilege','privilege_roles','modules'));
        }
        return redirect('/privileges')->with('error','Privilege not found');
    }
    public function update(Request $request){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$privilege = Privilege::Where('id',$request->id)->first();
        if($privilege) {
        	// $privilege->name = $request->name;
        	// if($privilege->save()) {
                foreach ($request->privileges as $key => $value) {
                    // $privilege_data = PrivilegeRoles::where(['privilege_id' => $privilege->id, 'module_id' => $key])
                    // ->update([
                    //     'is_visible' => (isset($value['is_visible']))?$value['is_visible']:0,
                    //     'is_create' => (isset($value['is_create']))?$value['is_create']:0,
                    //     'is_read' => (isset($value['is_read']))?$value['is_read']:0,
                    //     'is_edit' => (isset($value['is_edit']))?$value['is_edit']:0,
                    //     'is_delete' => (isset($value['is_delete']))?$value['is_delete']:0
                    // ]);
                    $privilege_data = PrivilegeRoles::updateOrCreate([
                        'privilege_id' => $privilege->id, 'module_id' => $key],[
                        'is_visible' => (isset($value['is_visible']))?$value['is_visible']:0,
                        'is_create' => (isset($value['is_create']))?$value['is_create']:0,
                        'is_read' => (isset($value['is_read']))?$value['is_read']:0,
                        'is_edit' => (isset($value['is_edit']))?$value['is_edit']:0,
                        'is_delete' => (isset($value['is_delete']))?$value['is_delete']:0
                    ]);
               }
               return redirect('/privileges')->with('success','Privilege updated successful');
         //    }
        	// else
         //       return redirect('/privileges')->with('error','Privilege update failed');
        }
        return redirect('/privileges')->with('error','Privilege not found');
    }
    public function delete($id){
        if(Auth::user()->privileges!=1) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$privilege = Privilege::Where('id',$id)->first();
        if($privilege) {
            if($privilege->delete())
               return redirect('/privileges')->with('success','Privilege deleted successful');
        	else
               return redirect('/privileges')->with('error','Privilege delete failed');
        }
        return redirect('/privileges')->with('error','Privilege not found');
    }
}
