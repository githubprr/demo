<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Slider;
use App\Blog;
use Auth;
use File;

class SliderController extends Controller
{
    public function index(){
        if(!$this->getPermission('sliders','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $sliders = Slider::get();
    	return view('slider.list', compact('sliders'));
    }
    public function add(){
        if(!$this->getPermission('sliders','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $blogs = Blog::get();
        return view('slider.add', compact('blogs'));
    }
    public function saveold(Request $request){
        if(!$this->getPermission('sliders','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if($request->hasFile('image')){
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $user_id = Auth::user()->id;
            $filename =  date("Y-m-d-h.i.s").'-'.$user_id .$ext;
            $filepath = public_path('uploads/sliders/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);

            $slider = new Slider;
            $slider->image = $filename;
            $slider->role_id = $request->role_id;
            $slider->blog_id = (isset($request->blog_id))?$request->blog_id:null;
            if($slider->save())
               return redirect('/slider')->with('success','Slider added successful');
        	else
               return redirect('/slider')->with('error','Slider add failed');
        }
    }

    public function save(Request $request){
        if(!$this->getPermission('sliders','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        if($request->hasFile('image')){
            $image = $request->profile_img_data;
            $folderPath = public_path('uploads/sliders/');
            
            if(!File::isDirectory($folderPath))
                File::makeDirectory($folderPath, 0777, true, true);

            $image_parts = explode(";base64,", $image);
            foreach ($image_parts as $key=>$image)
            {   
                if($key==0)
                {
                    $image_type_aux = explode("image/", $image);
                    $image_type = $image_type_aux[1];
                }
                else
                {
                    $image_base64 = base64_decode($image);
             
                    $imageName = uniqid() . '.png';
             
                    $imageFullPath = $folderPath.$imageName;
             
                    file_put_contents($imageFullPath, $image_base64);
                }
            }

            $slider = new Slider;
            $slider->image = $imageName;
            $slider->role_id = $request->role_id;
            $slider->blog_id = (isset($request->blog_id))?$request->blog_id:null;
            if($slider->save())
               return redirect('/slider')->with('success','Slider added successful');
            else
               return redirect('/slider')->with('error','Slider add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('sliders','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$slider = Slider::Where('id',$id)->first();
        if($slider) {
        	$blogs = Blog::get();
            return view('slider.edit',compact('slider','blogs'));
        }
        return redirect('/slider')->with('error','Slider not found');
    }
    public function updateold(Request $request){
        if(!$this->getPermission('sliders','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$slider = Slider::Where('id',$request->id)->first();
    	if($request->hasFile('image')){
            $image_path = public_path() . '/uploads/sliders/'.$slider->image;
            if(File::exists($image_path)) {
                File::delete($image_path);
            }
            $file= $_FILES['image']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $user_id = Auth::user()->id;
            $filename =  date("Y-m-d-h.i.s").'-'.$user_id .$ext;
            $filepath = public_path('uploads/sliders/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['image']['tmp_name'], $filepath.$filename);
            $slider->image = $filename;
        }
        $slider->role_id = $request->role_id;
        $slider->blog_id = (isset($request->blog_id))?$request->blog_id:null;
        if($slider->save())
           return redirect('/slider')->with('success','Slider updated successful');
    	else
           return redirect('/slider')->with('error','Slider update failed');
    }

    public function update(Request $request){
        if(!$this->getPermission('sliders','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $slider = Slider::Where('id',$request->id)->first();
        if($slider) {
            if($request->hasFile('image')){
                $image_path = public_path() . '/uploads/sliders/'.$slider->image;
                if(File::exists($image_path)) {
                    File::delete($image_path);
                }
                $image = $request->profile_img_data;
                $folderPath = public_path('uploads/sliders/');
                
                if(!File::isDirectory($folderPath))
                    File::makeDirectory($folderPath, 0777, true, true);

                $image_parts = explode(";base64,", $image);
                foreach ($image_parts as $key=>$image)
                {   
                    if($key==0)
                    {
                        $image_type_aux = explode("image/", $image);
                        $image_type = $image_type_aux[1];
                    }
                    else
                    {
                        $image_base64 = base64_decode($image);
                 
                        $imageName = uniqid() . '.png';
                 
                        $imageFullPath = $folderPath.$imageName;
                 
                        file_put_contents($imageFullPath, $image_base64);
                    }
                }
                $slider->image = $imageName;
            }
            $slider->role_id = $request->role_id;
            $slider->blog_id = (isset($request->blog_id))?$request->blog_id:null;
            if($slider->save())
               return redirect('/slider')->with('success','Slider updated successful');
            else
               return redirect('/slider')->with('error','Slider update failed');
        }
        return redirect('/slider')->with('error','Slider not found');
    }
    public function delete($id){
        if(!$this->getPermission('sliders','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$slider = Slider::Where('id',$id)->first();
        if($slider) {
            $image_path = public_path() . '/uploads/sliders/'.$slider->image;
            if(File::exists($image_path)) {
                File::delete($image_path);
            }
        	if($slider->delete())
               return redirect('/slider')->with('success','Slider deleted successful');
        	else
               return redirect('/slider')->with('error','Slider delete failed');
        }
        return redirect('/slider')->with('error','Slider not found');
    }
    public function apiGetSliders(){
        $user = auth()->guard('api')->user();
        $sliders = Slider::where('role_id',$user->privileges)->get();
        if($sliders) {
            return response()->json([
                'errorCode' => 0,
                'data' => $sliders,
                'message' => 'Get sliders successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get sliders failed'
        ]);
    }
}
