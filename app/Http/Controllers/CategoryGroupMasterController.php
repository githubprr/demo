<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CategoryGroupMaster;
use Auth;
use File;

class CategoryGroupMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('category_groups','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$category_groups = CategoryGroupMaster::withCount('categories')->get();
    	return view('category_group_master.list', compact('category_groups'));
    }
    public function add(){
        if(!$this->getPermission('category_groups','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        return view('category_group_master.add');
    }
    public function save(Request $request){
        if(!$this->getPermission('category_groups','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if($request->hasFile('image')){
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
            $filename =  $value.$ext;
            $filepath = public_path('uploads/category/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);

            $category_group = new CategoryGroupMaster;
            $category_group->name = $request->name;
            $category_group->name_hn = $request->name_hn;
            $category_group->name_mr = $request->name_mr;
            $category_group->image = $filename;
            $category_group->vendor_requisition_distance = $request->vendor_requisition_distance;
            if($category_group->save())
               return redirect('/categoryGroupMaster')->with('success','Category group added successful');
        	else
               return redirect('/categoryGroupMaster')->with('error','Category group add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('category_groups','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$category_group = CategoryGroupMaster::Where('id',$id)->first();
        if($category_group) {
            return view('category_group_master.edit',compact('category_group'));
        }
        return redirect('/categoryGroupMaster')->with('error','Category Group not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('category_groups','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$category_group = CategoryGroupMaster::Where('id',$request->id)->first();
        if($category_group) {
            if($request->hasFile('image')){
                $image_path = public_path() . '/uploads/category/'.$category_group->image;
                if(File::exists($image_path)) {
                    File::delete($image_path);
                }
                $file= $_FILES['image']['name'];
                $var=explode(".",$file);
                $ext='.'.end($var);
                $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename =  $value.$ext;
                $filepath = public_path('uploads/category/');

                if(!File::isDirectory($filepath))
                    File::makeDirectory($filepath, 0777, true, true);

                move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);
                $category_group->image = $filename;
            }
        	$category_group->name = $request->name;
            $category_group->name_hn = $request->name_hn;
            $category_group->name_mr = $request->name_mr;
            $category_group->vendor_requisition_distance = $request->vendor_requisition_distance;
        	if($category_group->save())
               return redirect('/categoryGroupMaster')->with('success','Category group updated successful');
        	else
               return redirect('/categoryGroupMaster')->with('error','Category group update failed');
        }
        return redirect('/categoryGroupMaster')->with('error','Category Group not found');
    }
    public function delete($id){
        if(!$this->getPermission('category_groups','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$category_group = CategoryGroupMaster::Where('id',$id)->first();
        if($category_group) {
        	if($category_group->delete())
               return redirect('/categoryGroupMaster')->with('success','Category group deleted successful');
        	else
               return redirect('/categoryGroupMaster')->with('error','Category group delete failed');
        }
        return redirect('/categoryGroupMaster')->with('error','Category Group not found');
    }
}
