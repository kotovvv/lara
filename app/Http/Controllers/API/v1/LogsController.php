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


    public function add(Request $request)
    {

        $log = new Log;

        $log->tel = $request->tel;
        $log->user_id = $request->user_id;
        if (isset($request->status_id)) {
            $log->status_id = $request->status_id;
        }
        if (isset($request->text)) {
            $log->text = $request->text;
        }

        $log->save();
    }

    public function tellog(Request $request)
    {

        $logs =  DB::table('logs')
            ->select('users.fio', 'statuses.name', 'statuses.color', 'logs.text', 'logs.created_at')
            ->join('statuses', 'logs.status_id', '=', 'statuses.id')
            ->join('users', 'logs.user_id', '=', 'users.id')
            ->where('logs.tel', $request->tel)
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
