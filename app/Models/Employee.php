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
    protected $guarded = ['id'];
    private static $JWTTOKEN = '';
    private static $loginUrl = "http://localhost/webhook_api/api/login";
    private static $getAllPinUrl = "http://localhost/webhook_api/api/get_all_pin";
    private static $getEmployeeUrl = "http://localhost/webhook_api/api/get_employees";
    private static $getUser = "http://localhost/webhook_api/api/get_userinfo";
    private static $setUser = "http://localhost/webhook_api/api/set_userinfo";
    private static $deleteUser = "http://localhost/webhook_api/api/delete_userinfo";

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

    public static function getDataEmployees(Request $request, $data = null)
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
                'wherein' => [
                    [
                        'field' => 'kar_id',
                        'values' => $pin
                    ]
                ],
                "raw" => $raw,
                "start" => $request->input('start'),
                "length" => $request->input('length')
            ];

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

            $dataSend = [
                'wherein' => [
                    [
                        'field' => 'kar_id',
                        'values' => $pin
                    ]
                ]
            ];

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

        try {
            $userFileName = "get_userinfo_{$now}.txt";

            $dataSend = [
                "trans_id" => "1",
                "cloud_id" => "{$request->machine[0]}",
                "pin" => "{$request->kar_id}"
            ];

            $userFile = '';
            $userInfo = Http::withToken(self::$JWTTOKEN)->post(self::$getUser, $dataSend);

            while (!Storage::exists($userFileName) && $waited < $maxWait) {
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
                    return $item['data']['pin'] == $request->kar_id && $item['cloud_id'] == $request->machine[0];
                })
                ->keyBy('cloud_id')
                ->values()
                ->toArray();

            $userFileJsonUnique = (object) $userFileJsonUnique[0];

            if ($request->addmachine) {
                $addFileName = "delete_userinfo_{$now}.txt";
                $add = '';

                foreach ($request->addmachine as $k => $v) {
                    $dataSend = [
                        "trans_id" => "1",
                        "cloud_id" => "{$v}",
                        "data" => [
                            "pin" => "{$request->kar_id}",
                            "name" => "{$request->nama}",
                            "privilege" => "{$userFileJsonUnique->data['privilege']}",
                            "password" => "{$userFileJsonUnique->data['template']}",
                            "rfid" => "{$userFileJsonUnique->data['template']}",
                            "template" => "{$userFileJsonUnique->data['template']}"
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
}
