<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Blog;
use App\BlogStats;
use App\StateMaster;
use App\DistrictMaster;
use App\TalukaMaster;
use App\PointMaster;
use App\PointHistory;
use App\Jobs\BlogNotification;
use Auth;
use File;
use Validator;

class BlogController extends Controller
{
    public function index(){
        if(!$this->getPermission('blogs','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $blogs = Blog::with('state')->with('district')->with('taluka')->orderBy('id','desc')->get();
    	return view('blog.list', compact('blogs'));
    }
    public function add(){
        if(!$this->getPermission('blogs','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $states = StateMaster::all();
        return view('blog.add', compact('states'));
    }
    public function save(Request $request){
        if(!$this->getPermission('blogs','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $validator = Validator::make($request->all(), [
            'state_id' => 'required|integer'
            // 'district_id' => 'required|integer',
            // 'taluka_id' => 'required|integer'
        ], [
            'state_id.required' => 'State is required'
            // 'district_id.required' => 'District is required',
            // 'taluka_id.required' => 'Taluka is required'
        ]);
        if($validator->fails())
            return back()->with('validations',$validator->errors());

        if($request->hasFile('media')){
            $file= $_FILES['media']['name'];
            $var=explode(".",$file);
            $ext='.'.end($var);
            $user_id = Auth::user()->id;
            $filename =  date("Y-m-d-h.i.s").'-'.$user_id .$ext;
            $filepath = public_path('uploads/blogs/');

            if(!File::isDirectory($filepath))
                File::makeDirectory($filepath, 0777, true, true);

            move_uploaded_file($_FILES['media']['tmp_name'], $filepath.$filename);

            $blog = new Blog;
            $blog->media = $filename;
            $blog->media_type = $request->media_type;
            $blog->title = $request->title;
            $blog->title_hn = $request->title_hn;
            $blog->title_mr = $request->title_mr;
            $blog->description = $request->description;
            $blog->description_hn = $request->description_hn;
            $blog->description_mr = $request->description_mr;
            $blog->state_id = $request->state_id;
            $blog->district_id = (isset($request->district_id))?$request->district_id:null;
            $blog->taluka_id = (isset($request->taluka_id))?$request->taluka_id:null;
            $blog->view_count = 0;
            $blog->like_count = 0;
            $blog->dislike_count = 0;
            $blog->share_count = 0;
            if($blog->save()) {
                dispatch(new BlogNotification($blog->id));
               return redirect('/blog')->with('success','Blog added successful');
            }
        	else
               return redirect('/blog')->with('error','Blog add failed');
        }
    }
    public function edit($id){
        if(!$this->getPermission('blogs','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$blog = Blog::Where('id',$id)->first();
        if($blog) {
            $states = StateMaster::all();
            $districts = DistrictMaster::where('state_id',$blog->state_id)->get();
            $talukas = TalukaMaster::where('district_id',$blog->district_id)->get();
            
            return view('blog.edit',compact('blog','states','districts','talukas'));
        }
        return redirect('/blog')->with('error','Blog not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('blogs','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$validator = Validator::make($request->all(), [
            'state_id' => 'required|integer'
            // 'district_id' => 'required|integer',
            // 'taluka_id' => 'required|integer'
        ], [
            'state_id.required' => 'State is required'
            // 'district_id.required' => 'District is required',
            // 'taluka_id.required' => 'Taluka is required'
        ]);
        if($validator->fails())
            return back()->with('validations',$validator->errors());
        
        $blog = Blog::Where('id',$request->id)->first();
    	if($blog) {
            if($request->hasFile('media')){
                $media_path = public_path() . '/uploads/blogs/'.$blog->media;
                if(File::exists($media_path)) {
                    File::delete($media_path);
                }
                $file= $_FILES['media']['name'];
                $var=explode(".",$file);
                $ext='.'.end($var);
                $user_id = Auth::user()->id;
                $filename =  date("Y-m-d-h.i.s").'-'.$user_id .$ext;
                $filepath = public_path('uploads/blogs/');

                if(!File::isDirectory($filepath))
                    File::makeDirectory($filepath, 0777, true, true);

                move_uploaded_file($_FILES['media']['tmp_name'], $filepath.$filename);
                $blog->media = $filename;
            }
            $blog->media_type = $request->media_type;
            $blog->title = $request->title;
            $blog->title_hn = $request->title_hn;
            $blog->title_mr = $request->title_mr;
            $blog->description = $request->description;
            $blog->description_hn = $request->description_hn;
            $blog->description_mr = $request->description_mr;
            $blog->state_id = $request->state_id;
            $blog->district_id = (isset($request->district_id))?$request->district_id:null;
            $blog->taluka_id = (isset($request->taluka_id))?$request->taluka_id:null;
            if($blog->save()) {
                dispatch(new BlogNotification($blog->id));
               return redirect('/blog')->with('success','Blog updated successful');
            }
        	else
               return redirect('/blog')->with('error','Blog update failed');
        }
    }
    public function delete($id){
        if(!$this->getPermission('blogs','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$blog = Blog::Where('id',$id)->first();
        if($blog) {
            $media_path = public_path() . '/uploads/blogs/'.$blog->media;
            if(File::exists($media_path)) {
                File::delete($media_path);
            }
        	if($blog->delete())
               return redirect('/blog')->with('success','Blog deleted successful');
        	else
               return redirect('/blog')->with('error','Blog delete failed');
        }
        return redirect('/blog')->with('error','Blog not found');
    }
    public function view($id){
        if(!$this->getPermission('blogs','is_read')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $blog = Blog::Where('id',$id)->first();
        if($blog) {
            return view('blog.details',compact('blog'));
        }
        return redirect('/blog')->with('error','Blog not found');
    }
    public function report(){
        if(!$this->getPermission('blogs','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $blogs = Blog::orderBy('id','desc')->get();
        return view('blog.report', compact('blogs'));
    }
    public function stats(){
    	if(!$this->getPermission('blogs','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $most_viewed = Blog::take(10)->orderBy('view_count','desc')->get();
        $most_likes = Blog::take(10)->orderBy('like_count','desc')->get();
        $most_dislikes = Blog::take(10)->orderBy('dislike_count','desc')->get();
        $most_shared = Blog::take(10)->orderBy('share_count','desc')->get();
        return view('blog.stats', compact('most_viewed','most_likes','most_dislikes','most_shared'));
    }
    public function apiGetBlogs(){
        $user = auth()->guard('api')->user();
        $header = request()->header('lang');
        $lang = 'title';
        $description_lang = 'description';
        $column = 'title';
        if($header=='hn') {
            $lang = 'title_hn as title';
            $description_lang = 'description_hn as description';
            $column = 'title_hn';
        }
        else if($header=='mr') {
            $lang = 'title_mr as title';
            $description_lang = 'description_mr as description';
            $column = 'title_mr';
        }
        //$blogs = Blog::whereNotNull($column)->where('state_id',$user->state_id)->where('district_id',$user->district_id)->where('taluka_id',$user->taluka_id)->select('id',$lang,$description_lang,'media','media_type','state_id','district_id','taluka_id','view_count','like_count','dislike_count','share_count','created_at','updated_at')->orderBy('id','desc')->get();
        $taluka_blogs = collect(Blog::whereNotNull($column)->where('state_id',$user->state_id)->where('district_id',$user->district_id)->where('taluka_id',$user->taluka_id)->pluck('id'));
        $district_blogs = collect(Blog::whereNotNull($column)->where('state_id',$user->state_id)->where('district_id',$user->district_id)->whereNull('taluka_id')->pluck('id'));
        $state_blogs = collect(Blog::whereNotNull($column)->where('state_id',$user->state_id)->whereNull('district_id')->whereNull('taluka_id')->pluck('id'));
        
        $taluka_district = $taluka_blogs->merge($district_blogs);
        $blog_ids = $taluka_district->merge($state_blogs);
        
        $blogs = Blog::whereNotNull($column)->whereIn('id',$blog_ids)->select('id',$lang,$description_lang,'media','media_type','state_id','district_id','taluka_id','view_count','like_count','dislike_count','share_count','created_at','updated_at')->orderBy('id','desc')->get();
        if($blogs) {
            return response()->json([
                'errorCode' => 0,
                'data' => $blogs,
                'message' => 'Get blogs successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get blogs failed'
        ]);
    }
    public function apiGetBlogDetails($id){
        $user = auth()->guard('api')->user();
        $header = request()->header('lang');
        $lang = 'title';
        $description_lang = 'description';
        $column = 'title';
        if($header=='hn') {
            $lang = 'title_hn as title';
            $description_lang = 'description_hn as description';
            $column = 'title_hn';
        }
        else if($header=='mr') {
            $lang = 'title_mr as title';
            $description_lang = 'description_mr as description';
            $column = 'title_mr';
        }
        $blog = Blog::whereNotNull($column)->where('id',$id)->select('id',$lang,$description_lang,'media','media_type','state_id','district_id','taluka_id','view_count','like_count','dislike_count','share_count','created_at','updated_at')->first();
        if($blog) {
            $blogStats = BlogStats::where('user_id',$user->id)->where('blog_id',$id)->first();
            if($blogStats) {
                // $blogStats->view_count=$blogStats->view_count+1;
                // $blogStats->save();
            }
            else {
                $blogStat = new BlogStats;
                $blogStat->user_id = $user->id;
                $blogStat->blog_id = $id;
                $blogStat->view_count = 1;
                $blogStat->is_like = 0;
                $blogStat->is_dislike = 0;
                $blogStat->share_count = 0;
                $blogStat->save();

                $points_master = PointMaster::where('slug','blog_read')->first();
                if($points_master) {
                    $wallet_points = $points_master->point;
                    $user->wallet_points += $wallet_points;
                    $user->save();

                    $message = "Added ".$wallet_points." points for read blog";
                    $pointHistoryShare = new PointHistory;
                    $pointHistoryShare->message = $message;
                    $pointHistoryShare->points = $wallet_points;
                    $pointHistoryShare->user_id = $user->id;
                    $pointHistoryShare->save();
                }
                $blog->view_count=$blog->view_count+1;
                $blog->save();
            }

            $blogStatsNew = BlogStats::where('user_id',$user->id)->where('blog_id',$id)->first();
            $blog['blog_stats'] = $blogStatsNew;
            return response()->json([
                'errorCode' => 0,
                'data' => $blog,
                'message' => 'Get blog details successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get blog details failed'
        ]);
    }
    public function apiBlogLike($id){
        $user = auth()->guard('api')->user();
        $blogStats = BlogStats::where('user_id',$user->id)->where('blog_id',$id)->first();
        if($blogStats) {
            $blogStats->is_like=1;
            $blogStats->is_dislike=0;
            $blogStats->save();

            $blogLikeCount = BlogStats::where('blog_id',$id)->where('is_like',1)->count();
            $blogDislikeCount = BlogStats::where('blog_id',$id)->where('is_dislike',1)->count();
            $blogMaster = Blog::where('id',$id)->first();
            $blogMaster->like_count=$blogLikeCount;
            $blogMaster->dislike_count=$blogDislikeCount;
            $blogMaster->save();
            return response()->json([
                'errorCode' => 0,
                'message' => 'Blog like successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Blog like failed'
        ]);
    }
    public function apiBlogDislike($id){
        $user = auth()->guard('api')->user();
        $blogStats = BlogStats::where('user_id',$user->id)->where('blog_id',$id)->first();
        if($blogStats) {
            $blogStats->is_like=0;
            $blogStats->is_dislike=1;
            $blogStats->save();
            
            $blogLikeCount = BlogStats::where('blog_id',$id)->where('is_like',1)->count();
            $blogDislikeCount = BlogStats::where('blog_id',$id)->where('is_dislike',1)->count();
            $blogMaster = Blog::where('id',$id)->first();
            $blogMaster->like_count=$blogLikeCount;
            $blogMaster->dislike_count=$blogDislikeCount;
            $blogMaster->save();
            return response()->json([
                'errorCode' => 0,
                'message' => 'Blog dislike successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Blog dislike failed'
        ]);
    }
    public function apiBlogShare($id){
        $user = auth()->guard('api')->user();
        $blogStats = BlogStats::where('user_id',$user->id)->where('blog_id',$id)->first();
        if($blogStats) {
            if($blogStats->share_count==0) {
                $points_master = PointMaster::where('slug','blog_share')->first();
                if($points_master) {
                    $wallet_points = $points_master->point;
                    $user->wallet_points += $wallet_points;
                    $user->save();

                    $message = "Added ".$wallet_points." points for share blog";
                    $pointHistoryShare = new PointHistory;
                    $pointHistoryShare->message = $message;
                    $pointHistoryShare->points = $wallet_points;
                    $pointHistoryShare->user_id = $user->id;
                    $pointHistoryShare->save();
                }
            }
            $blogStats->share_count=$blogStats->share_count+1;
            $blogStats->save();

            $blogMaster = Blog::where('id',$id)->first();
            $blogMaster->share_count=$blogMaster->share_count+1;
            $blogMaster->save();
            return response()->json([
                'errorCode' => 0,
                'message' => 'Blog share successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Blog share failed'
        ]);
    }
}
