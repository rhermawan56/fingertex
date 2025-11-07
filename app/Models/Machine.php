<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Machine extends Model
{
    use HasFactory;
    protected $table = 'ms_mesin';
    protected $primaryKey = 'msn_id';
    protected $guarded = ['msn_id'];
    const CREATED_AT = 'msn_creation';
    public $timestamps = true;
    const UPDATED_AT = null;

    private static $JWTTOKEN = '';
    private static $timezone = 'Asia/Jakarta';
    private static $loginUrl = "http://103.76.15.27/webhook_api/api/login";
    private static $setTimeUrl = "http://103.76.15.27/webhook_api/api/set_time";
    private static $restartMachineUrl = "http://103.76.15.27/webhook_api/api/restart_device";

    public static function loginApi()
    {
        try {
            $response = Http::post(
                self::$loginUrl,
                [
                    'username' => env('API_USERNAME'),
                    'password' => env('API_PASSWORD')
                ]
            );

            $response = $response->json();
            self::$JWTTOKEN = $response['token'];

            return response()->json([
                'status' => true,
                'messages' => 'Success Login'
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'messages' => $e->getMessage()
            ], 400);
        }
    }

    public static function fetchdata(Request $request)
    {
        $where = [];
        $wherein = [];
        $wherenotin = [];
        $raw = [];
        $exclude = ['wherein', 'wherenotin', 'raw'];

        $data = Machine::query();

        $columns = function () use ($request) {
            $col = collect($request->input('columns'));

            return $col->filter(function ($item) {
                return $item['search']['value'];
            });
        };

        $order = function () use ($request) {
            $order = null;
            $ord = collect($request->input('order')[0]);

            if ($ord) {
                if ($ord['column'] > 0) {
                    $order = [
                        'column' => $request->input('columns')[$ord['column']]['data'],
                        'dir' => $ord['dir']
                    ];
                }
            }

            return $order;
        };

        if ($columns()) {
            foreach ($columns() as $k => $v) {
                $after = [];
                $before = [];
                $none = [];

                if (in_array($v['data'], $after)) {
                    $data = $data->where($v['data'], 'like', "{$v['search']['value']}%");
                } else if (in_array($v['data'], $before)) {
                    $data = $data->where($v['data'], 'like', "%{$v['search']['value']}");
                } else if (in_array($v['data'], $none)) {
                    $data = $data->where($v['data'], 'like', "{$v['search']['value']}");
                } else {
                    $data = $data->where($v['data'], 'like', "%{$v['search']['value']}%");
                }
            }
        }

        if ($order()) {
            $data = $data->orderBy("{$order()['column']}", "{$order()['dir']}");
        }

        $reponse['rows'] = clone $data;
        $reponse['data'] = $data->offset($request->input('start'))->limit($request->input('length'));
        $reponse['data'] = $reponse['data']->get();
        $reponse['rows'] = $reponse['rows']->count();

        return $reponse;
    }

    public static function resettime(Request $request)
    {
        self::loginApi();
        $machine = Machine::all();

        try {
            $responseData = collect();
            foreach ($machine as $k => $v) {
                $dataSend = [
                    "trans_id" => $k + 1,
                    "cloud_id" => "{$v->cloud_id}",
                    "timezone" => self::$timezone
                ];

                $setTime = Http::withToken(self::$JWTTOKEN)->post(self::$setTimeUrl, $dataSend);
                $setTime->json();

                $responseData->push([
                    'status' => $setTime['data']['success'],
                    'cloud_id' => $v->cloud_id
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $responseData,
                'messages' => 'success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => [],
                'messages' => $e->getMessage()
            ], 400);
        }
    }

    public static function restartmachine(Request $request)
    {
        self::loginApi();
        $machine = Machine::where('cloud_id', $request->cloud_id)->first();

        try {
            $dataSend = [
                "trans_id" => "1",
                "cloud_id" => "{$machine->cloud_id}"
            ];
            $restartMachine = Http::withToken(self::$JWTTOKEN)->post(self::$restartMachineUrl, $dataSend);
            $restartMachine->json();

            return response()->json([
                'status' => true,
                'data' => $restartMachine['data']['success'],
                'messages' => 'success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => [],
                'messages' => $e->getMessage()
            ], 400);
        }
    }
}
