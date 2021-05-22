<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Log;
use DB;
use Debugbar;

class LogsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function getlogonid(Request $request,$lid_id)
    {
        $getlog = $request->all();
    //  Debugbar::info($insertItem);

    if ($getlog['api_key'] != env('API_KEY')) return response('Key incorect', 403);
    return Log::where('lid_id',$lid_id)->get();

    }


    public function add(Request $request)
    {

        $log = new Log;

        $log->tel = $request->tel;
        $log->lid_id = $request->lid_id;
        $log->user_id = $request->user_id;
        if (isset($request->status_id)) {
            $log->status_id = $request->status_id;
        }
        if (isset($request->text)) {
            $log->text = $request->text;
            $a_lid = [
                'text' => $request->text,
                'updated_at' => Now()
              ];
            DB::table('lids')->where('id', $request->id)->update($a_lid);
        }

        $log->save();
    }

    public function tellog(Request $request)
    {

        $logs =  DB::table('logs')
            ->select('users.fio', 'statuses.name', 'statuses.color', 'logs.text', 'logs.created_at')//,'logs.tel'
            ->leftJoin('statuses', 'logs.status_id', '=', 'statuses.id')
            ->join('users', 'logs.user_id', '=', 'users.id')
            ->where('logs.lid_id', $request->lid_id)
            ->reorder('logs.created_at', 'desc')
            ->get();
        return $logs;
    }

    public function userlog(Request $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
