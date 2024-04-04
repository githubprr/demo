<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Group;
use App\GroupUser;
use App\GroupRequest;
use Auth;
use File;
use Validator;

class GroupController extends Controller
{
    public function index(){
        if(!$this->getPermission('groups','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $groups = Group::withCount('group_user')->with('user')->orderBy('id','desc')->get();
    	return view('group.list', compact('groups'));
    }
    public function edit($id){
        if(!$this->getPermission('groups','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $group = Group::where('id',$id)->with('user')->first();
        if($group) {
            return view('group.edit', compact('group'));
        }
        return redirect('/group')->with('error','Group not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('groups','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $group = Group::Where('id',$request->id)->first();
        if($group) {
            $group->govt_reg = $request->govt_reg;
            if($group->save())
               return redirect('/group')->with('success','Group updated successful');
            else
               return redirect('/group')->with('error','Group update failed');
        }
        return redirect('/group')->with('error','Group not found');
    }
    public function groupUsers($id){
    	if(!$this->getPermission('groups','is_read')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $group_users = GroupUser::where('group_id',$id)->with('user')->orderBy('id','desc')->get();
        return view('group.group_user', compact('group_users'));
    }
    public function delete($id){
        if(!$this->getPermission('groups','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$group = Group::Where('id',$id)->first();
        if($group) {
            $group_users = GroupUser::Where('group_id',$id)->delete();
        	$group_requests = GroupRequest::Where('group_id',$id)->delete();
        	if($group->delete())
               return redirect('/group')->with('success','Group deleted successful');
        	else
               return redirect('/group')->with('error','Group delete failed');
        }
        return redirect('/group')->with('error','Group not found');
    }
}
