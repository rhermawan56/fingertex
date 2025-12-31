<?php

namespace App\Models;

// use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory;
    protected $table = 'employees';
    protected $primaryKey = 'employee_id';
    protected $guarded = ['employee_id'];
    const CREATED_AT = null;
    const UPDATED_AT = null;

    private static $JWTTOKEN = '';
    private static $loginUrl = "http://103.76.15.27/webhook_api/api/login";
    private static $getAllPinUrl = "http://103.76.15.27/webhook_api/api/get_all_pin";
    private static $getEmployeeUrl = "http://103.76.15.27/webhook_api/api/get_employees";
    private static $getUser = "http://103.76.15.27/webhook_api/api/get_userinfo";
    private static $setUser = "http://103.76.15.27/webhook_api/api/set_userinfo";
    private static $deleteUser = "http://103.76.15.27/webhook_api/api/delete_userinfo";

    public function employee_machines()
    {
        return $this->hasMany(EmployeeMachine::class, 'employee_id', 'employee_id');
    }

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

    public static function getDataEmployeesBackup(Request $request, $data = null)
    {
        self::loginApi();
        $now = date('Y_m_d');
        $filename = "get_userid_list_{$now}.txt";

        try {

            foreach ($data as $k => $v) {
                $dataSend = [
                    "trans_id" => $k + 1,
                    "cloud_id" => $v->cloud_id
                ];

                $response = Http::withToken(self::$JWTTOKEN)->post(self::$getAllPinUrl, $dataSend);
                $response = $response->json();
            }

            $maxWait = 15;
            $interval = 0.5;
            $waited = 0;

            while (!Storage::exists($filename) && $waited < $maxWait) {
                usleep($interval * 1_000_000);
                $waited += $interval;
            }

            $file = '';
            $file = Storage::get($filename);
            $lines = array_filter(array_map('trim', explode("\n", $file)));
            $fileJson = [];
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if ($decoded !== null) {
                    $fileJson[] = $decoded;
                }
            }

            $fileJsonUnique = collect($fileJson)
                ->keyBy('cloud_id')
                ->values()
                ->toArray();

            $pin = collect($fileJsonUnique)
                ->pluck('data.pin_arr')
                ->flatten()
                // ->unique()
                ->values()
                ->toArray();

            $raw = [];
            $excludeFilter = ['machine', 'cloud_id'];
            $columns = function () use ($request) {
                $col = collect($request->input('columns'));

                return $col->filter(function ($item) {
                    return $item['search']['value'];
                });
            };

            if ($columns()) {
                foreach ($columns() as $k => $v) {
                    $after = [];
                    $before = [];
                    $none = [];

                    if (!in_array($v['data'], $excludeFilter)) {
                        if (in_array($v['data'], $after)) {
                            array_push($raw, "{$v['data']} like '{$v['search']['value']}%'");
                        } else if (in_array($v['data'], $before)) {
                            array_push($raw, "{$v['data']} like '%{$v['search']['value']}'");
                        } else if (in_array($v['data'], $none)) {
                            array_push($raw, "{$v['data']} like '{$v['search']['value']}'");
                        } else {
                            array_push($raw, "{$v['data']} like '%{$v['search']['value']}%'");
                        }
                    }
                }
            }

            $dataSend = [
                // 'wherein' => [
                //     [
                //         'field' => 'kar_idxx',
                //         'values' => $pin
                //     ]
                // ],
                "raw" => $raw,
                "start" => $request->input('start'),
                "length" => $request->input('length')
            ];

            if (count($pin) < 1000) {
                $dataSend['wherein'][] = [
                    'field' => 'kar_id',
                    'values' => $pin
                ];
            }

            $requestEmployee = Http::withToken(self::$JWTTOKEN)->post(self::$getEmployeeUrl, $dataSend);
            $requestEmployee = $requestEmployee->json();

            foreach ($requestEmployee['data'] as $k => &$v) {
                $cloudId = collect($fileJsonUnique)->filter(function ($item) use ($v) {
                    return in_array($v['kar_id'], $item['data']['pin_arr']);
                })->values()->pluck('cloud_id')->toArray();

                $machine = collect($data)->filter(function ($item) use ($cloudId) {
                    return in_array($item['cloud_id'], $cloudId);
                })->values();

                $v['cloud_id'] = $machine->map(function ($item) {
                    return $item['cloud_id'];
                });

                $v['machine'] = $machine->map(function ($item) {
                    return $item['msn_name'];
                });
            }

            return response()->json([
                'status' => true,
                'data' => $requestEmployee['data'],
                'rows' => $requestEmployee['rows'],
                'messages' => 'success',
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'data' => [],
                'rows' => 0,
                'messages' => $e->getMessage()
            ], 400);
        }
    }

    public static function getDataEmployees(Request $request, $data = null)
    {
        // dd($request->columns);
        $columns = function () use ($request) {
            return collect($request->columns)->filter(function ($item) {
                return $item['search']['value'];
            })->toArray();
        };

        $orders = function () use ($request) {
            $ord = null;
            if ($request->order) {
                if ($request->order[0]['column'] > 0) {
                    $ord = "{$request->columns[$request->order[0]['column']]['data']} {$request->order[0]['dir']}";
                }
            }

            return $ord;
        };

        $data = self::query();

        if ($columns()) {
            foreach ($columns() as $k => $v) {
                $after = [];
                $before = [];
                $none = [];
                $excludeFilter = ['machine', 'cloud_id'];

                if (!in_array($v['data'], $excludeFilter)) {
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
        }

        $response['rows'] = clone $data;
        $response['rows'] = $response['rows']->count();
        $response['data'] = $data->offset($request->start)->limit($request->length)->get();

        return response()->json([
            'status' => true,
            'data' => $response['data'],
            'rows' => $response['rows'],
            'messages' => 'ok'
        ], 400);
    }

    public static function getDataEmployee($id, $data)
    {
        self::loginApi();
        $now = date('Y_m_d');
        $filename = "get_userid_list_{$now}.txt";

        try {
            $file = '';

            if (Storage::exists($filename)) {
                $file = Storage::get($filename);
            } else {
                foreach ($data as $k => $v) {
                    $dataSend = [
                        "trans_id" => $k + 1,
                        "cloud_id" => $v->cloud_id
                    ];

                    $response = Http::withToken(self::$JWTTOKEN)->post(self::$getAllPinUrl, $dataSend);
                    $response = $response->json();
                }

                $maxWait = 15;
                $interval = 0.5;
                $waited = 0;

                while (!Storage::exists($filename) && $waited < $maxWait) {
                    usleep($interval * 1_000_000);
                    $waited += $interval;
                }

                $file = Storage::get($filename);
            }

            $lines = array_filter(array_map('trim', explode("\n", $file)));
            $fileJson = [];
            foreach ($lines as $line) {
                $decoded = json_decode($line, true);
                if ($decoded !== null) {
                    $fileJson[] = $decoded;
                }
            }

            $fileJsonUnique = collect($fileJson)
                ->keyBy('cloud_id')
                ->values()
                ->toArray();

            $pin = collect($fileJsonUnique)
                ->pluck('data.pin_arr')
                ->flatten()
                ->unique()
                ->filter(function ($item) use ($id) {
                    return $item == $id;
                })
                ->values()
                ->toArray();

            $company = '';
            $newPin = [];
            foreach ($pin as $k => $v) {
                if (substr($v, 0, 2) == 80) {
                    $company = 'SINAR TERANG';
                    $newPin[] = substr($v, 2);
                } else {
                    $newPin[] = $v;
                }
            }

            $dataSend = [
                'wherein' => [
                    [
                        'field' => 'kar_id',
                        'values' => $newPin
                    ]
                ]
            ];

            if ($company) {
                $dataSend['company'] = $company;
            }

            $requestEmployee = Http::withToken(self::$JWTTOKEN)->post(self::$getEmployeeUrl, $dataSend);
            $requestEmployee = $requestEmployee->json();

            foreach ($requestEmployee['data'] as $k => &$v) {
                if ($company == 'SINAR TERANG') {
                    $v['kar_id'] = "80{$v['kar_id']}";
                }
                $cloudId = collect($fileJsonUnique)->filter(function ($item) use ($v) {
                    return in_array($v['kar_id'], $item['data']['pin_arr']);
                })->values()->pluck('cloud_id')->toArray();

                $machine = collect($data)->filter(function ($item) use ($cloudId) {
                    return in_array($item['cloud_id'], $cloudId);
                })->values();

                $v['cloud_id'] = $machine->map(function ($item) {
                    return $item['cloud_id'];
                });

                $v['machine'] = $machine->map(function ($item) {
                    return $item['msn_name'];
                });
            }

            return response()->json([
                'status' => true,
                'data' => $requestEmployee['data'][0],
                'rows' => $requestEmployee['rows'],
                'messages' => 'success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'data' => [],
                'rows' => 0,
                'messages' => $e->getMessage()
            ], 400);
        }
    }

    public static function processData(Request $request)
    {
        self::loginApi();
        $now = date('Y_m_d');
        $maxWait = 15;
        $interval = 0.5;
        $waited = 0;
        $template = null;
        $name  = null;
        $employeeCheck = Employee::where('kar_id', $request->kar_id)->whereNotNull('template')->first();

        try {
            if (!$employeeCheck) {
                $userFileName = "get_userinfo_{$now}.txt";
    
                $dataSend = [
                    "trans_id" => "1",
                    "cloud_id" => "{$request->machine[0]}",
                    "pin" => "{$request->kar_id}"
                ];
    
                $userFile = '';
                $userInfo = Http::withToken(self::$JWTTOKEN)->post(self::$getUser, $dataSend);
    
                // while (!Storage::exists($userFileName) && $waited < $maxWait) {
                while ($waited < $maxWait) {
                    usleep($interval * 1_000_000);
                    $waited += $interval;
                }
    
                $userFile = Storage::get($userFileName);
                $linesUserFile = array_filter(array_map('trim', explode("\n", $userFile)));
                $userFileJson = [];
                foreach ($linesUserFile as $line) {
                    $decoded = json_decode($line, true);
                    if ($decoded !== null) {
                        $userFileJson[] = $decoded;
                    }
                }
    
                $userFileJsonUnique = collect($userFileJson)
                    ->filter(function ($item) use ($request) {
                        if (isset($item['data']['pin'])) {
                            return $item['data']['pin'] == $request->kar_id && $item['cloud_id'] == $request->machine[0];
                        }
                    })
                    ->keyBy('cloud_id')
                    ->values()
                    ->toArray();
    
                $userFileJsonUnique = (object) $userFileJsonUnique[0];
                $template = $userFileJsonUnique->data['template'];
                $name = $userFileJsonUnique->data['name'];
                Employee::where('kar_id', $request->kar_id)->update(['template' => $template, 'employee_name_machine' => $name]);
            } else {
                $template = $employeeCheck->template;
                $name = $employeeCheck->employee_name_machine;
            }

            if ($request->addmachine) {
                $addFileName = "set_userinfo_{$now}.txt";
                $add = '';

                foreach ($request->addmachine as $k => $v) {
                    $dataSend = [
                        "trans_id" => "1",
                        "cloud_id" => "{$v}",
                        "data" => [
                            "pin" => "{$request->kar_id}",
                            "name" => "{$name}",
                            // "privilege" => "{$userFileJsonUnique->data['privilege']}",
                            "privilege" => "1",
                            "password" => "{$template}",
                            "rfid" => "{$template}",
                            "template" => "{$template}"
                        ]
                    ];

                    $add = Http::withToken(self::$JWTTOKEN)->post(self::$setUser, $dataSend);
                    $add = $add->json();
                }
            }

            if ($request->removemachine) {
                $removeFileName = "delete_userinfo_{$now}.txt";
                $removeFile = '';
                foreach ($request->removemachine as $k => $v) {
                    $dataSend = [
                        "trans_id" => "1",
                        "cloud_id" => "{$v}",
                        "pin" => "{$request->kar_id}"
                    ];

                    $remove = Http::withToken(self::$JWTTOKEN)->post(self::$deleteUser, $dataSend);
                    $remove = $remove->json();
                    EmployeeMachine::where([
                        'kar_id' => $request->kar_id,
                        'cloud_id' => $v
                    ])->delete();
                }
            }

            return response()->json([
                'status' => true,
                'data' => [],
                'rows' => 0,
                'messages' => "To add or delete data from the machine, wait for 20 seconds and ensure that the machine is connected to the internet."
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'data' => [],
                'rows' => 0,
                'messages' => $e->getMessage()
            ], 400);
        }
    }

    public static function syncdata(Request $request)
    {
        self::loginApi();
        $machine = Machine::all()->toArray();
        $syncdata = [];

        foreach ($machine as $k => $v) {
            $transId = $k + 1;
            $dataSend = [
                "trans_id" => "{$transId}",
                "cloud_id" => "{$v['cloud_id']}"
            ];

            try {
                $syncdata = Http::withToken(self::$JWTTOKEN)->post(self::$getAllPinUrl, $dataSend);
                $syncdata = $syncdata->json();
            } catch (\Exception $th) {
                return response()->json([
                    'status' => false,
                    'messages' => $th->getMessage()
                ], 500);
            }
        }

        return response()->json([
            'status' => true,
            'messages' => $syncdata['data']
        ], 200);
    }
}
