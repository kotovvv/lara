<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use App\Models\Lid;
use App\Models\Log;
use App\Models\Import;

class ProvidersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Provider::all();
    }

    public function getall()
    {
        return Provider::all();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function status_provider(Request $request)
    {
        // $request->validate([
        //     'provider_id' => 'required',
        // ]);
        $req = $request->all();
        $provider_id = $req['provider_id'];
        $datefrom = $req['datefrom'];
        $dateto = $req['dateto'];
        $cur_date = date('Y-m-d');
        $return = [];
        if (isset($provider_id)) {

            $sql = "SELECT DISTINCT CAST(`start` AS DATE) 'start' FROM `imports` WHERE `provider_id` = " . $provider_id . " AND (CAST(`start` AS DATE) BETWEEN '" . $datefrom . "' AND '" . $dateto . "')";
            $a_dateadd = DB::select(DB::raw($sql));
            $sql = "SELECT COUNT(id) n FROM `lids` WHERE `provider_id` = '". $provider_id."'";
            $all_lids = DB::select(DB::raw($sql));
            $return['all'] = $all_lids;
            if ($a_dateadd) {

                foreach ($a_dateadd as $dateadd) {
                    $sql = "SELECT s.`color`,s.`name`, COUNT(`status_id`) n FROM `logs` l LEFT JOIN `statuses` s ON (s.`id` = l.`status_id`) WHERE `status_id` > 0 AND `lid_id` IN (SELECT id FROM `lids` WHERE `provider_id` = " . $provider_id . " AND CAST(`created_at` AS DATE ) = '" . $dateadd->start . "') GROUP BY `status_id` ORDER BY s.`order` ASC ";

                    $a_statuses = DB::select(DB::raw($sql));
                    $return['allstatuses'][] = ['date' => $dateadd->start, 'statuses' => $a_statuses];
                }
               // $sql = "SELECT s.`color`,s.`name`, COUNT(`status_id`) n FROM `logs` l LEFT JOIN `statuses` s ON (s.`id` = l.`status_id`) WHERE `status_id` > 0 AND CAST(l.`created_at` AS DATE ) = '" . $cur_date . "' AND `lid_id` IN (SELECT id FROM `lids` WHERE `provider_id` = " . $provider_id . " ) GROUP BY `status_id` ORDER BY s.`order` ASC ";
                $sql = "SELECT *, count(*) n from (SELECT DISTINCT  s.`color`, s.`name` FROM `logs` l LEFT JOIN `statuses` s ON (s.`id` = l.`status_id`) WHERE `status_id` > 0 AND CAST(l.`created_at` AS DATE ) BETWEEN '" . $datefrom . "' AND '" . $dateto . "' AND `lid_id` IN (SELECT id FROM `lids` WHERE `provider_id` = " . $provider_id ." )  ORDER BY s.`order` ASC ) t1 GROUP BY name";

                $a_statuses = DB::select(DB::raw($sql));
                $return['allstatuses'][] = ['date' => 'Итог', 'statuses' => $a_statuses];
            }
        }
        //     $statuses =  DB::select(DB::raw("SELECT
        //     `statuses`.`name`
        //     , COUNT(`statuses`.`name`) AS `hm`
        // FROM
        //     `providers`
        //     LEFT JOIN `lids`
        //         ON (`providers`.`id` = `lids`.`provider_id`)
        //     LEFT JOIN `logs`
        //         ON (`lids`.`tel` = `logs`.`tel`)
        //     LEFT JOIN `statuses`
        //         ON (`logs`.`status_id` = `statuses`.`id`)
        // WHERE (`providers`.`id` = " . $data['provider_id'] . ")
        // GROUP BY `statuses`.`name`, `providers`.`id`
        // ORDER BY `hm` DESC"));
        //     $req['hm'] = DB::select(DB::raw("SELECT DATE(`created_at`) dateadd,COUNT(*) hm FROM `lids` WHERE `provider_id` = " . $data['provider_id']));
        //     $req['statuses'] = $statuses;

        return $return;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
        ]);

        $data = $request->all();
        // Debugbar::info($data);
        if (isset($data['id'])) {
            // Debugbar::info('update');
            if (Provider::where('id', $data['id'])->update($data)) {
                return response('Provider updated', 200);
            } else return response('Provider updated error', 301);
        } else {
            // Debugbar::info('save');
            if (Provider::create($data)) {
                return response('Provider added', 200);
            } else return response('Provider add error', 301);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $provider = Provider::find($id);
        if (!$provider) {
            return response()->json([
                "status" => false,
                "message" => "provider not found"
            ])->setStatusCode(404);
        }

        return $provider;
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
        Provider::where('id', $id)->delete();
        $tels =  Lid::select('tel')->where('provider_id', '=', $id);
        Log::whereIn('tel', $tels)->delete();
        Lid::where('provider_id', '=', $id)->delete();
        Import::where('provider_id', '=', $id)->delete();
    }
}
