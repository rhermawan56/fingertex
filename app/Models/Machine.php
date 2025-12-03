<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

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
    private static $getlog = "http://103.76.15.27/webhook_api/api/get_attlog";
    private static $getemployee = "http://103.76.15.27/webhook_api/api/get_employees";
    private static $attendanceUrl = "http://103.76.15.27/webhook_api/api/attendance_insert";
    private static $employeesShiftUrl = "http://103.76.15.27/webhook_api/api/getemployeeshift";

    private static $desc = [
        "0" => 'masuk',
        "1" => 'pulang',
        "2" => 'istirahat',
        "3" => 'masuk istirahat',
        "4" => 'masuk lembur',
        "5" => 'pulang lembur',
        "6" => 'masuk rapat',
        "7" => 'keluar rapat',
    ];

    private static $verify = [
        "1" => "finger",
        "2" => "password",
        "3" => "card",
        "4" => "face",
        "6" => "vein",
        "7" => "QR",
    ];

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

    public static function getattendance(Request $request)
    {
        self::loginApi();
        $machine = Machine::where('cloud_id', $request->cloud_id)->first();
        $currentDate = date('Y-m-d');
        $lastDate = Carbon::parse($currentDate)->subDay();
        $lastDate = $lastDate->toDateString();

        try {
            $dataSend = [
                "trans_id" => "1",
                "cloud_id" => "{$request['cloud_id']}",
                "start_date" => $currentDate,
                "end_date" => $currentDate
            ];

            $getlog = Http::withToken(self::$JWTTOKEN)->post(self::$getlog, $dataSend);
            $getlog->json();

            $logdata = $getlog['data']['data'] ?? [];

            if (!$logdata) {
                return response()->json([
                    'status' => false,
                    'data' => $logdata,
                    'messages' => "Tidak ada data di tanggal {$lastDate} s.d {$currentDate}",
                ], 404);
            }

            foreach ($logdata as $k => $v) {
                $dataSend = [
                    'kar_id' => $v['pin'],
                    'company' => $machine->company
                ];

                $getemployee = Http::withToken(self::$JWTTOKEN)->post(self::$getemployee, $dataSend);
                $getemployee = $getemployee->json();
                $employeeData = $getemployee['data'] ?? [];

                if ($employeeData) {
                    $dataInsert = [
                        'tgl_absen' => explode(' ', $v['scan_date'])[0],
                        'jam' => explode(' ', $v['scan_date'])[1],
                        'status' => self::$desc[$v['status_scan']],
                        'karyawan_id' => $v['pin'],
                        'karyawan_name' => $employeeData[0]['nama'],
                        'cloud_id' => $request['cloud_id'],
                        'company' => $machine->company,
                        'create_date' => date('Y-m-d'),
                        'validation' => '1',
                        'verification_method' => self::$verify[$v['verify']],
                    ];

                    $dataSend = [
                        "kar_id" => $dataInsert['karyawan_id'],
                        "company" => $dataInsert['company']
                    ];

                    $check = Attendance::where(['karyawan_id' => $v['pin'], 'tgl_absen' => $dataInsert['tgl_absen'], 'status' => self::$desc[$v['status_scan']]])->count();
                    $date = Carbon::parse($dataInsert['tgl_absen'])->subDay();
                    $shift2 = [5, 6, 7, 8];
                    
                    if ($check == 0) {
                        Attendance::insert($dataInsert);

                        if ($dataInsert['status'] == 'pulang' && in_array(substr($dataInsert['jam'], 0, 2), $shift2)) {
                            $dataInsert['tgl_absen'] = $date->toDateString();
                            $dataSend['raw'] = [
                                "drtgl <= '{$dataInsert['tgl_absen']}'",
                                "ketgl >= '{$dataInsert['tgl_absen']}'"
                            ];

                            $employeeshift = Http::withToken(self::$JWTTOKEN)->post(self::$employeesShiftUrl, $dataSend);
                            $employeeshift = $employeeshift->json();

                            $employeeshiftData = $employeeshift['data'] ?? [];

                            if ($employeeshiftData) {
                                if ($employeeshiftData[0]['id_shift'] == 'Shift 1') {
                                    $dataInsert['tgl_absen'] = explode(' ', $v['scan_date'])[0];
                                }
                            }
                        }
                        
                        Http::withToken(self::$JWTTOKEN)->post(self::$attendanceUrl, $dataInsert);
                    } else {
                        Attendance::where([
                            'karyawan_id' => $v['pin'],
                            'tgl_absen' => $dataInsert['tgl_absen'],
                            'status' => self::$desc[$v['status_scan']],
                            'cloud_id' => $request['cloud_id']
                        ])->update([
                            'jam' => explode(' ', $v['scan_date'])[1]
                        ]);

                        if ($dataInsert['status'] == 'pulang' && in_array(substr($dataInsert['jam'], 0, 2), $shift2)) {
                            $dataInsert['tgl_absen'] = $date->toDateString();
                            $dataSend['raw'] = [
                                "drtgl <= '{$dataInsert['tgl_absen']}'",
                                "ketgl >= '{$dataInsert['tgl_absen']}'"
                            ];

                            $employeeshift = Http::withToken(self::$JWTTOKEN)->post(self::$employeesShiftUrl, $dataSend);
                            $employeeshift = $employeeshift->json();

                            $employeeshiftData = $employeeshift['data'] ?? [];

                            if ($employeeshiftData) {
                                if ($employeeshiftData[0]['id_shift'] == 'Shift 1') {
                                    $dataInsert['tgl_absen'] = explode(' ', $v['scan_date'])[0];
                                }
                            }
                        }

                        Http::withToken(self::$JWTTOKEN)->post(self::$attendanceUrl, $dataInsert);
                    }
                }
            }

            return response()->json([
                'status' => true,
                'data' => $logdata,
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
