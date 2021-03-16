<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Lid;
use Mockery\Undefined;

class LidsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Lid::all()->where('active',1)->where('user_id',2);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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
        Log::alert($request);
    }

    public function newlids(Request $request)
    {
        //  Log::alert($request);
        // $data = $this->validate($request, [
        //     'provider_id' => 'requered',
        //     'user_id' => 'requered',
        //     'data' => 'requered'
        //    ]);
            // Log::alert($request->all());
            $data = $request->all();


           foreach ($data['data'] as $lid) {
             $a_lid = [
               'name' => $lid['name'],
                'email' => $lid['email'],
                'user_id' => $data['user_id'],
             ];
             if (isset($data['provider_id']))  $a_lid['provider_id'] = $data['provider_id'];
             if (isset($data['status_id']))  $a_lid['status_id'] = $data['status_id'];

            $status_id = !empty($data['status_id'])?$data['status_id']:null;
            Lid::updateOrCreate(['tel' => $lid['tel']],$a_lid);

          }
          return response('Lids imported', 200);
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
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
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
