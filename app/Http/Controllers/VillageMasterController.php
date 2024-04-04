<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DistrictMaster;
use App\TalukaMaster;
use App\VillageMaster;
use Auth;
use File;
use Excel;
use DataTables;

class VillageMasterController extends Controller
{
    public function index(Request $request){
        if(!$this->getPermission('villages','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        // $villages = VillageMaster::with('taluka')->withCount('users')->get();
        // return view('village_master.list', compact('villages'));
        $districts = DistrictMaster::get();
        return view('village_master.list', compact('districts'));
    }
    public function getVillageByTalukaIdDatatable($id, Request $request){
        // $villages = VillageMaster::with('taluka')->withCount('users')->get();
        // return view('village_master.list', compact('villages'));
        $taluka_id = $id;
        if ($request->ajax()) {
            $data = VillageMaster::with('taluka')->withCount('users')->where('taluka_id',$taluka_id);
            return Datatables::eloquent($data)
                    // ->addIndexColumn()
                    ->addColumn('taluka', function($taluka) {
                        return $taluka->name;
                    })
                    ->addColumn('taluka_hn', function($taluka) {
                        return $taluka->name_hn;
                    })
                    ->addColumn('taluka_mr', function($taluka) {
                        return $taluka->name_mr;
                    })
                    ->addColumn('action', function($row){
                            $editUrl = url('/editVillage/'.$row->id);
                            $deleteUrl = url('/editVillage/'.$row->id);
                            $btn = '<a href="'.$editUrl.'" class="btnEdit"><i class="fa fa-edit"></i></a>';
                            if($row->users_count>0)
                                $btn .= '<a href="" class="btnNoDelete" data-message="You can not delete this village because some users belongs to this village"><i class="fa fa-ban"></i></a>';
                            else
                                $btn .= '<a href="'.$deleteUrl.'" class="btnDelete" onclick="return confirm(\'Are you sure?\')"><i class="fa fa-trash"></i></a>';
                            return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
        }
    }
    public function add(){
        if(!$this->getPermission('villages','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $districts = DistrictMaster::get();
    	return view('village_master.add', compact('districts'));
    }
    public function save(Request $request){
        if(!$this->getPermission('villages','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $village = new VillageMaster;
        $village->taluka_id  = $request->taluka_id;
        $village->name = $request->name;
        $village->name_hn = $request->name_hn;
        $village->name_mr = $request->name_mr;
        if($village->save())
           return redirect('/villageMaster')->with('success','Village added successful');
    	else
           return redirect('/villageMaster')->with('error','Village add failed');
    }
    public function edit($id){
        if(!$this->getPermission('villages','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$village = VillageMaster::Where('id',$id)->first();
        if($village) {
            $districts = DistrictMaster::get();
        	$taluka = TalukaMaster::where('id',$village->taluka_id)->first();
            $talukas = TalukaMaster::where('district_id',$taluka->district_id)->get();
            $district_id = $taluka->district_id;
            return view('village_master.edit',compact('districts', 'talukas', 'village', 'district_id'));
        }
        return redirect('/villageMaster')->with('error','Village not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('villages','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$village = VillageMaster::Where('id',$request->id)->first();
        if($village) {
            $village->taluka_id  = $request->taluka_id;
        	$village->name = $request->name;
            $village->name_hn = $request->name_hn;
            $village->name_mr = $request->name_mr;
        	if($village->save())
               return redirect('/villageMaster')->with('success','Village updated successful');
        	else
               return redirect('/villageMaster')->with('error','Village update failed');
        }
        return redirect('/villageMaster')->with('error','Village not found');
    }
    public function delete($id){
        if(!$this->getPermission('villages','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$village = VillageMaster::Where('id',$id)->first();
        if($village) {
        	if($village->delete())
               return redirect('/villageMaster')->with('success','Village deleted successful');
        	else
               return redirect('/villageMaster')->with('error','Village delete failed');
        }
        return redirect('/villageMaster')->with('error','Village not found');
    }
    public function getVillageByTaluka($id){
        $villages = VillageMaster::Where('taluka_id',$id)->get();
        return response()->json($villages);
    }
    public function importVillages(Request $request)
    {
        if(!$this->getPermission('villages','is_create')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $request->validate([
            'import_file' => 'required'
        ]);

        $path = $request->file('import_file')->getRealPath();
        $data = Excel::load($path)->get();
        if($data->count()){
            $talukas = TalukaMaster::get();
            foreach ($data as $key => $value) {
                $filtered_collection = $talukas->filter(function ($item) use ($value) {
                    return $item->name==$value->taluka;
                })->first();
                if(isset($filtered_collection) && isset($filtered_collection->id)) {
                    if(isset($value->village) && trim($value->village)!='') {
                        $arr[] = [
                            'name' => $value->village,
                            'name_hn' => (isset($value->village_hn))?$value->village_hn:null,
                            'name_mr' => (isset($value->village_mr))?$value->village_mr:null,
                            'taluka_id' => $filtered_collection->id
                        ];
                    }
                }
            }

            if(!empty($arr)){
                foreach (array_chunk($arr,1000) as $t)  
                {
                     VillageMaster::insert($t); 
                }
            }
        }

        return back()->with('success', 'Villages import successfully.');
    }
    public function truncate()
    {
        VillageMaster::query()->truncate();
        return back();
    }
}
