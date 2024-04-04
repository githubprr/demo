<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CategoryMaster;
use App\CategoryGroupMaster;
use Auth;
use File;

class CategoryMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('categories','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $categories = CategoryMaster::with('category_group')->withCount('sub_categories')->withCount('items')->withCount('shop_items')->orderBy('id','desc')->get();
    	return view('category_master.list', compact('categories'));
    }
    public function add(){
        if(!$this->getPermission('categories','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $category_groups = CategoryGroupMaster::get();
        return view('category_master.add', compact('category_groups'));
    }
    public function save(Request $request){
        if(!$this->getPermission('categories','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
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

            $category = new CategoryMaster;
            $category->item_category_group_id = $request->item_category_group_id;
            $category->name = $request->name;
            $category->name_hn = $request->name_hn;
            $category->name_mr = $request->name_mr;
            $category->image = $filename;
            if($category->save())
               return redirect('/categoryMaster')->with('success','Category added successful');
        	else
               return redirect('/categoryMaster')->with('error','Category add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('categories','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$category = CategoryMaster::Where('id',$id)->first();
    	if($category) {
            $category_groups = CategoryGroupMaster::get();
            return view('category_master.edit',compact('category', 'category_groups'));
        }
        return redirect('/categoryMaster')->with('error','Category not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('categories','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$category = CategoryMaster::Where('id',$request->id)->first();
        if($category) {
            if($request->hasFile('image')){
                $image_path = public_path() . '/uploads/category/'.$category->image;
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
                $category->image = $filename;
            }
            $category->item_category_group_id = $request->item_category_group_id;
        	$category->name = $request->name;
            $category->name_hn = $request->name_hn;
            $category->name_mr = $request->name_mr;
        	if($category->save())
               return redirect('/categoryMaster')->with('success','Category updated successful');
        	else
               return redirect('/categoryMaster')->with('error','Category update failed');
        }
        return redirect('/categoryMaster')->with('error','Category not found');
    }
    public function delete($id){
        if(!$this->getPermission('categories','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$category = CategoryMaster::Where('id',$id)->first();
        if($category) {
        	if($category->delete())
               return redirect('/categoryMaster')->with('success','Category deleted successful');
        	else
               return redirect('/categoryMaster')->with('error','Category delete failed');
        }
        return redirect('/categoryMaster')->with('error','Category not found');
    }
    public function getCategoryByCategoryGroup($id){
        $categories = CategoryMaster::Where('item_category_group_id',$id)->get();
        return response()->json($categories);
    }
}
