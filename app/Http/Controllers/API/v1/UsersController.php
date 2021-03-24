<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use DB;
use Debugbar;
use Hash;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::All();
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
    public function update(Request $request)
    {

        $data = $request->all();
        // Debugbar::info($data);
        if (isset($data['password'])) $password = Hash::make($data['password']);
        if (isset($data['id'])) {
            //  Debugbar::info('update');
            $arr= [];
            $arr['name'] = $data['name'];
            $arr['active'] = $data['active'];
            $arr['role_id'] = $data['role_id'];
            $arr['fio'] = $data['fio'];
            if (User::where('id', $data['id'])->update($arr)) {
                if (isset($data['password'])) {
                  $user = User::find($data['id']);
                    $user->password = $password;
                    $user->save();
                }
                return response('User updated', 200);
            } else return response('User updated error', 301);
        } else {
            // Debugbar::info('save');
            if ($user = User::create($data)) {
                if (isset($data['password'])) {
                    $user->password = $password;
                    $user->save();
                }
                return response('User added', 200);
            } else return response('User add error', 301);
        }
    }


    public function getusers()
    {
        return User::select(['users.*', DB::raw('(SELECT COUNT(user_id) FROM lids WHERE lids.user_id = users.id) as hmlids ')])

            ->where('users.role_id', '>', 1)
            ->where('users.active', 1)
            ->leftJoin('lids', 'users.id', '=', 'lids.user_id')
            ->orderBy('users.role_id', 'asc')
            ->groupBy('users.id')
            ->get();
    }

    public function getroles()
    {
        return Role::All();
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
