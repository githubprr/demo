<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CategoryGroupMaster;
use App\CategoryMaster;
use App\SubcategoryMaster;
use Auth;
use File;

class SubcategoryMasterController extends Controller
{
    public function index(){
        if(!$this->getPermission('sub_categories','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $subcategories = SubcategoryMaster::with('category')->withCount('items')->withCount('shop_items')->get();
        return view('subcategory_master.list', compact('subcategories'));
    }
    public function add(){
        if(!$this->getPermission('sub_categories','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $category_groups = CategoryGroupMaster::get();
    	return view('subcategory_master.add', compact('category_groups'));
    }
    public function save(Request $request){
        if(!$this->getPermission('sub_categories','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if($request->hasFile('image')){
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
            $filename =  $value.$ext;
            $filepath = public_path('uploads/subcategory/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);

            $subcategory = new SubcategoryMaster;
            $subcategory->item_categories_id  = $request->item_categories_id;
            $subcategory->name = $request->name;
            $subcategory->name_hn = $request->name_hn;
            $subcategory->name_mr = $request->name_mr;
            $subcategory->image = $filename;
            if($subcategory->save())
               return redirect('/subcategoryMaster')->with('success','Subcategory added successful');
        	else
               return redirect('/subcategoryMaster')->with('error','Subcategory add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('sub_categories','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$subcategory = SubcategoryMaster::with('category')->Where('id',$id)->first();
        if($subcategory) {
            $category_groups = CategoryGroupMaster::get();
        	$categories = CategoryMaster::where('item_category_group_id', $subcategory->category->item_category_group_id)->get();
            return view('subcategory_master.edit',compact('category_groups', 'categories', 'subcategory'));
        }
        return redirect('/subcategoryMaster')->with('error','Subcategory not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('sub_categories','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$subcategory = SubcategoryMaster::Where('id',$request->id)->first();
        if($subcategory) {
            if($request->hasFile('image')){
                $image_path = public_path() . '/uploads/subcategory/'.$subcategory->image;
                if(File::exists($image_path)) {
                    File::delete($image_path);
                }
                $file= $_FILES['image']['name'];
                $var=explode(".",$file);
                $ext='.'.end($var);
                $value = preg_replace('/(0)\.(\d+) (\d+)/', '$3$1$2', microtime());
                $filename =  $value.$ext;
                $filepath = public_path('uploads/subcategory/');

                if(!File::isDirectory($filepath))
                    File::makeDirectory($filepath, 0777, true, true);

                move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);
                $subcategory->image = $filename;
            }
            $subcategory->item_categories_id  = $request->item_categories_id;
        	$subcategory->name = $request->name;
            $subcategory->name_hn = $request->name_hn;
            $subcategory->name_mr = $request->name_mr;
        	if($subcategory->save())
               return redirect('/subcategoryMaster')->with('success','Subcategory updated successful');
        	else
               return redirect('/subcategoryMaster')->with('error','Subcategory update failed');
        }
        return redirect('/subcategoryMaster')->with('error','Subcategory not found');
    }
    public function delete($id){
        if(!$this->getPermission('sub_categories','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$subcategory = SubcategoryMaster::Where('id',$id)->first();
        if($subcategory) {
        	if($subcategory->delete())
               return redirect('/subcategoryMaster')->with('success','Subcategory deleted successful');
        	else
               return redirect('/subcategoryMaster')->with('error','Subcategory delete failed');
        }
        return redirect('/subcategoryMaster')->with('error','Subcategory not found');
    }
    public function getSubcategoryByCategory($id){
        $subcategories = SubcategoryMaster::Where('item_categories_id',$id)->get();
        return response()->json($subcategories);
    }
}
