<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use DB;
use Debugbar;
use Hash;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    // return User::All();
    return User::select(['users.*', DB::raw('(SELECT COUNT(user_id) FROM lids WHERE lids.user_id = users.id) as hmlids ')])

      // ->where('users.role_id', '>', 1)
      // ->where('users.active', 1)
      // ->leftJoin('lids', 'users.id', '=', 'lids.user_id')
      ->orderBy('users.role_id', 'asc')
      // ->groupBy('users.id')
      ->get();
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
      $arr = [];
      $arr['name'] = $data['name'];
      $arr['active'] = $data['active'];
      $arr['role_id'] = $data['role_id'];
      $arr['fio'] = $data['fio'];
      $arr['group_id'] = $data['group_id'];
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

  public function status_users(Request $request)
  {
    // Debugbar::info($sql);
    $repoprt = [];
    $req = $request->All();
    $a_users = $req['users'];
$datefrom = $req['datefrom'];
$dateto = $req['dateto'];

    $sql = "SELECT '' color,'Пользователи' text ";
    foreach ($a_users as $user_id) {
      $sql .= ",(SELECT fio FROM `users` WHERE id = " . $user_id . ") u" . $user_id;
    }
    $fio = DB::select(DB::raw($sql));
    $repoprt[] =  $fio[0];

    $sql = "SELECT '' color,'Всего лидов' text ";
    foreach ($a_users as $user_id) {
      $sql .= ",(SELECT COUNT(*) FROM `lids` WHERE `user_id` =  " . $user_id . ") u" . $user_id;
    }
    $all = DB::select(DB::raw($sql));
    $repoprt[] =  $all[0];



    // SELECT id, NAME,color FROM `statuses` WHERE `active` = 1 ORDER BY `order`
    $statuses = DB::select(DB::raw("SELECT id, `name`, color FROM `statuses` WHERE `active` = 1 ORDER BY `order` ASC"));
    foreach ($statuses as $status) {

      $sql = "SELECT '".$status->color."' color,'". $status->name ."'  text ";
      foreach ($a_users as $user_id) {
          $sql .= ",(SELECT COUNT(*) FROM `logs` WHERE `user_id` = " . $user_id . " AND DATE(`updated_at`) BETWEEN '".$datefrom."' AND '".$dateto."' AND `status_id` = " . $status->id . ") u" . $user_id;
        }
        $sts = DB::select(DB::raw($sql));
        $repoprt[] =  $sts[0];
      }
      return $repoprt;
    // SELECT 'Пользователи',(SELECT fio FROM `users` WHERE id = 1) n1 ,(SELECT fio FROM `users` WHERE id = 3) n3
    // SELECT 'Всего лидов', (SELECT COUNT(*) FROM `lids` WHERE `user_id` = 3) n3, (SELECT COUNT(*) FROM `lids` WHERE `user_id` = 4) n4
    // SELECT COUNT(*) FROM `logs` WHERE `user_id` = 2 AND `status_id` = 4

  }


  public function getusers()
  {

    return User::select(['users.*', DB::raw('(SELECT COUNT(user_id) FROM lids WHERE lids.user_id = users.id) as hmlids ')])

      ->where('users.role_id', '>', 1)
      ->where('users.active', 1)
      // ->leftJoin('lids', 'users.id', '=', 'lids.user_id')
      ->orderBy('users.role_id', 'asc')
      // ->groupBy('users.id')
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
