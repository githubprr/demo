<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Ticket;
use Auth;

class TicketController extends Controller
{
    public function index(){
        if(!$this->getPermission('tickets','is_visible')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $tickets = Ticket::with('user')->orderBy('id', 'desc')->get();
        return view('ticket.list', compact('tickets'));
    }
    public function edit($id){
        if(!$this->getPermission('tickets','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $ticket = Ticket::Where('id',$id)->first();
        if($ticket) {
            return view('ticket.edit', compact('ticket'));
        }
        return redirect('/requisitions')->with('error','Requisition not found');
    }
    public function update(Request $request){
        if(!$this->getPermission('tickets','is_edit')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
        $ticket = Ticket::where('id',$request->id)->first();
        if($ticket) {
            $ticket->answer = $request->answer;
            if($ticket->save())
               return redirect('/tickets')->with('success','Ticket updated successful');
            else
               return redirect('/tickets')->with('error','Ticket update failed');
        }
        return redirect('/requisitions')->with('error','Requisition not found');
    }
    public function delete($id){
    	if(!$this->getPermission('tickets','is_delete')) return redirect('/unauthorized_access')->with('error','Unauthorized Access');
    	$ticket = Ticket::Where('id',$id)->first();
        if($ticket) {
            if($ticket->delete())
               return redirect('/tickets')->with('success','Ticket deleted successful');
            else
               return redirect('/tickets')->with('error','Ticket delete failed');
        }
        return redirect('/requisitions')->with('error','Requisition not found');
    }
    public function apiIndex(){
        $user = auth()->guard('api')->user();
        $tickets = Ticket::where('user_id', $user->id)->get();
        if(sizeof($tickets)>0) {
            return response()->json([
                'errorCode' => 0,
                'data' => $tickets,
                'message' => 'Get tickets successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Get tickets failed'
        ]);
    }
    public function apiSave(Request $request){
        $user = auth()->guard('api')->user();
        $valid = validator($request->only('subject','question'), [
            'subject' => 'required',
            'question' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $ticket = new Ticket;
        $ticket->subject = urldecode($request->subject);
        $ticket->question = urldecode($request->question);
        $ticket->user_id = $user->id;
        if($ticket->save()) {
            $ticket->ticket_id = "GMTKT".$ticket->id;
            $ticket->save();
        }

        return response()->json([
            'errorCode' => 0,
            'message' => 'Ticket saved successful'
        ]);
    }
    public function apiDetails($id){
        $ticket = Ticket::Where('id',$id)->first();
        if($ticket) {
            return response()->json([
                'errorCode' => 0,
                'data' => $ticket,
                'message' => 'Ticket get successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Ticket get failed'
        ]);
    }
    public function apiUpdate(Request $request){
        $valid = validator($request->only('id','question'), [
            'id' => 'required',
            'question' => 'required'
        ]);

        if ($valid->fails()) {
            $jsonError=response()->json($valid->errors()->all(), 400);
            return response()->json([
                'errorCode' => 2,
                'validation' => $jsonError->original,
                'message' => 'Validation errors'
            ]);
        }

        $ticket = Ticket::where('id',$request->id)->first();
        if($ticket) {
            if(isset($ticket->answer)) {
                return response()->json([
                    'errorCode' => 1,
                    'message' => 'Resolved ticket can not update'
                ]);
            }
            $ticket->question = $request->question;
            $ticket->save();

            return response()->json([
                'errorCode' => 0,
                'message' => 'Ticket updated successful'
            ]);
        }

        return response()->json([
            'errorCode' => 1,
            'message' => 'Ticket update failed'
        ]);
    }
    public function apiDelete($id){
        $ticket = Ticket::Where('id',$id)->first();
        if($ticket->delete()) {
            return response()->json([
                'errorCode' => 0,
                'message' => 'Ticket deleted successful'
            ]);
        }
        return response()->json([
            'errorCode' => 1,
            'message' => 'Ticket delete failed'
        ]);
    }
}
