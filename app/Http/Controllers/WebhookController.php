<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeMachine;
use App\Models\Machine;
use Carbon\Carbon;

class WebhookController extends BaseController
{
    private $date;
    private $JWTTOKEN = 0;
    private $ip1 = 'http://103.76.15.27';
    private $ip2 = 'http://119.18.157.213';

    private $loginUrl = "webhook_api/api/login";
    private $employeesUrl = "webhook_api/api/get_employees";
    private $attendanceUrl = "webhook_api/api/attendance_insert";
    private $employeesShiftUrl = "webhook_api/api/getemployeeshift";
    private $getAllLog = "webhook_api/api/get_attlog";

    private $desc = [
        "0" => 'masuk',
        "1" => 'pulang',
        "2" => 'istirahat',
        "3" => 'masuk istirahat',
        "4" => 'masuk lembur',
        "5" => 'pulang lembur',
        "6" => 'masuk rapat',
        "7" => 'keluar rapat',
    ];
    private $verify = [
        "1" => "finger",
        "2" => "password",
        "3" => "card",
        "4" => "face",
        "6" => "vein",
        "7" => "QR",
    ];

    public function __construct()
    {
        $this->date = date('Y_m_d');
    }

    public function receive(Request $request)
    {
        $status = 'FAIL';
        $body = $request->getContent();

        $jsonData = $body . "\n";
        $jsonData = json_decode($jsonData);

        switch ($jsonData->type) {
            case 'attlog':
                $status = $this->attlog($body, $jsonData, 'attlog');
                break;

            case 'get_userid_list':
                $status = $this->userlist($body, $jsonData, 'get_userid_list');
                break;

            default:
                $status = $this->logs($body, $jsonData, $jsonData->type);
                break;
        }

        $connectionResponse = [];

        try {
            $response = Http::timeout(3)->get($this->ip1);

            $connectionResponse = [
                'status' => 'true',
                'ip' => $this->ip1,
                'desc' => 'utama',
                'messages' => $response->status(),
            ];

            $this->loginUrl = "{$this->ip1}/{$this->loginUrl}";
            $this->employeesUrl = "{$this->ip1}/{$this->employeesUrl}";
            $this->attendanceUrl = "{$this->ip1}/{$this->attendanceUrl}";
            $this->employeesShiftUrl = "{$this->ip1}/{$this->employeesShiftUrl}";
        } catch (\Exception $e) {
            $connectionResponse = [
                'status' => 'false',
                'ip' => $this->ip1,
                'desc' => 'utama',
                'messages' => $e->getMessage(),
            ];
        }

        if ($connectionResponse['status'] == 'false') {
            try {
                $response = Http::timeout(3)->get($this->ip2);

                $connectionResponse = [
                    'status' => 'true',
                    'ip' => $this->ip2,
                    'desc' => 'backup',
                    'messages' => $response->status(),
                ];

                $this->loginUrl = "{$this->ip2}/{$this->loginUrl}";
                $this->employeesUrl = "{$this->ip2}/{$this->employeesUrl}";
                $this->attendanceUrl = "{$this->ip2}/{$this->attendanceUrl}";
                $this->employeesShiftUrl = "{$this->ip2}/{$this->employeesShiftUrl}";
            } catch (\Exception $e) {
                $connectionResponse = [
                    'status' => 'false',
                    'ip' => $this->ip2,
                    'desc' => 'backup',
                    'messages' => $e->getMessage(),
                    'messages' => '2 isp down!!'
                ];
            }
        }

        if ($jsonData->type === 'connection') {
            return $connectionResponse;
        }

        if ($connectionResponse['status'] === 'true') {
            return $this->dataProcessing($status, $jsonData);
        } else {
            unset($connectionResponse['ip']);
            return $connectionResponse;
        }

        // $filename = 'data.txt';
        // $data = '';
        // if (Storage::exists($filename)) {
        //     $data = Storage::get($filename);
        // }

        // $lines = array_filter(explode("\n", trim($data)));
        // $jsonArray = [];

        // foreach ($lines as $line) {
        //     $decoded = json_decode($line, true);
        //     $jsonArray[] = $decoded ? $decoded : $line;
        // }

        // $data .= $body . "\n";

        // Storage::put($filename, $data);

        // return response('OK', 200);
    }

    private function attlog($body, $jsonData, $name)
    {
        $filename = "{$name}_{$this->date}.txt";

        $data = '';
        if (Storage::exists($filename)) {
            $data = Storage::get($filename);
        }

        $data .= $body . "\n";

        try {
            Storage::put($filename, $data);

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('FAIL', 400);
        }
    }

    private function userlist($body, $jsonData, $name)
    {
        $filename = "{$name}_{$this->date}.txt";

        $data = '';
        if (Storage::exists($filename)) {
            $data = Storage::get($filename);
        }

        $data .= $body . "\n";

        try {
            Storage::put($filename, $data);

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('FAIL', 400);
        }
    }

    private function logs($body, $jsonData, $name)
    {
        $filename = "{$name}_{$this->date}.txt";

        $data = '';
        if (Storage::exists($filename)) {
            $data = Storage::get($filename);
        }

        $data .= $body . "\n";

        try {
            Storage::put($filename, $data);

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('FAIL', 400);
        }
    }

    private function dataProcessing($status, $data)
    {
        if ($status->getOriginalContent() == 'OK') {
            $include = ['attlog', 'get_userid_list'];

            if (in_array($data->type, $include)) {
                $dataLogin = [
                    'username' => env('API_USERNAME'),
                    'password' => env('API_PASSWORD')
                ];

                try {
                    $responseLogin = Http::post($this->loginUrl, $dataLogin);
                    $responseLogin = $responseLogin->json();
                    $this->JWTTOKEN = $responseLogin['token'];
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => false,
                        'messages' => $e->getMessage()
                    ], 400);
                }

                $machine = Machine::where('cloud_id', $data->cloud_id)->first();

                if ($data->type == 'attlog') {
                    try {
                        $dataSend = [
                            'kar_id' => $data->data->pin,
                            'company' => $machine->company
                        ];

                        // dd($dataSend);

                        $responseData = Http::withToken($this->JWTTOKEN)->post($this->employeesUrl, $dataSend);
                        $responseData = $responseData->json();
                        $responseData = $responseData['data'][0];

                        // return $responseData;
                        // return explode(' ', $data->data->scan)[0];

                        $dataInsert = [
                            'tgl_absen' => explode(' ', $data->data->scan)[0],
                            'jam' => explode(' ', $data->data->scan)[1],
                            'status' => $this->desc[$data->data->status_scan],
                            'karyawan_id' => $responseData['kar_id'],
                            'karyawan_name' => $responseData['nama'],
                            'cloud_id' => $data->cloud_id,
                            'company' => $machine->company,
                            'create_date' => date('Y-m-d H:i:s'),
                            'validation' => '1',
                            'verification_method' => $this->verify[$data->data->verify]
                        ];

                        $dataSend = [
                            "kar_id" => $dataInsert['karyawan_id'],
                            "company" => $dataInsert['company']
                        ];

                        $check = Attendance::where(['karyawan_id' => $responseData['kar_id'], 'tgl_absen' => explode(' ', $data->data->scan)[0], 'status' => $this->desc[$data->data->status_scan]])->count();
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

                                $employeeshift = Http::withToken($this->JWTTOKEN)->post($this->employeesShiftUrl, $dataSend);
                                $employeeshift = $employeeshift->json();

                                $employeeshiftData = $employeeshift['data'] ?? [];

                                if ($employeeshiftData) {
                                    if ($employeeshiftData[0]['id_shift'] == 'Shift 1') {
                                        $dataInsert['tgl_absen'] = explode(' ', $data->data->scan)[0];
                                    }
                                }
                            }

                            Http::withToken($this->JWTTOKEN)->post($this->attendanceUrl, $dataInsert);
                        } else {
                            Attendance::where([
                                'karyawan_id' => $responseData['kar_id'],
                                'tgl_absen' => explode(' ', $data->data->scan)[0],
                                'status' => $this->desc[$data->data->status_scan],
                                'cloud_id' => $data->cloud_id
                            ])->update([
                                'jam' => explode(' ', $data->data->scan)[1]
                            ]);

                            if ($dataInsert['status'] == 'pulang' && in_array(substr($dataInsert['jam'], 0, 2), $shift2)) {
                                $dataInsert['tgl_absen'] = $date->toDateString();
                                $dataSend['raw'] = [
                                    "drtgl <= '{$dataInsert['tgl_absen']}'",
                                    "ketgl >= '{$dataInsert['tgl_absen']}'"
                                ];

                                $employeeshift = Http::withToken($this->JWTTOKEN)->post($this->employeesShiftUrl, $dataSend);
                                $employeeshift = $employeeshift->json();

                                $employeeshiftData = $employeeshift['data'] ?? [];

                                if ($employeeshiftData) {
                                    if ($employeeshiftData[0]['id_shift'] == 'Shift 1') {
                                        $dataInsert['tgl_absen'] = explode(' ', $data->data->scan)[0];
                                    }
                                }
                            }
                            Http::withToken($this->JWTTOKEN)->post($this->attendanceUrl, $dataInsert);
                        }

                        return response()->json([
                            'status' => true,
                            'messages' => 'Data berhasil disimpan'
                        ], 200);
                    } catch (\Exception $e) {
                        return response()->json([
                            'status' => false,
                            'messages' => $e->getMessage()
                        ], 400);
                    }
                }

                if ($data->type == 'get_userid_list') {
                    try {
                        $dataSend = [
                            "company" => "kahaptex",
                            "lokasi" => '1',
                            "start" => 0,
                            "length" => 3000
                        ];
                        $employeeKahap = Http::withToken($this->JWTTOKEN)->post($this->employeesUrl, $dataSend);
                        $employeeKahap = $employeeKahap->json();
                        $employeeKahapData = $employeeKahap['data'] ?? [];
                        $employeeKahapFilter = [];

                        $dataSend = [
                            "company" => "sinar terang",
                            "start" => 0,
                            "length" => 3000
                        ];
                        $employeeSinter = Http::withToken($this->JWTTOKEN)->post($this->employeesUrl, $dataSend);
                        $employeeSinter = $employeeSinter->json();
                        $employeeSinterData = $employeeSinter['data'] ?? [];
                        $employeeSinterFilter = [];

                        $pin = $data->data->pin_arr;
                        $pinSinter = collect($pin)->filter(function ($item) {
                            return substr($item, 0, 2) == 80;
                        })->toArray();

                        $pinKahap = collect($pin)->filter(function ($item) {
                            return substr($item, 0, 2) !== 80;
                        })->toArray();

                        if ($employeeKahapData) {
                            $employeeKahapFilter = collect($employeeKahapData)->whereIn('kar_id', $pinKahap)->values()->all();

                            if ($employeeKahapFilter) {
                                $employeeKahapFilter = collect($employeeKahapFilter)->map(function ($item) {
                                    $item['company'] = 'PT. KAHAPTEX';

                                    return $item;
                                });
                            }
                        }

                        if ($employeeSinterData) {
                            $employeeSinterData = collect($employeeSinterData)->map(function ($item) {
                                $item['kar_id'] = "80{$item['kar_id']}";

                                return $item;
                            });

                            $employeeSinterFilter = collect($employeeSinterData)->whereIn('kar_id', $pinSinter)->values()->all();

                            if ($employeeSinterFilter) {
                                $employeeSinterFilter = collect($employeeSinterFilter)->map(function ($item) {
                                    $item['company'] = 'PT. SINAR TERANG';

                                    return $item;
                                });
                            }
                        }

                        if ($employeeKahapFilter) {
                            foreach ($employeeKahapFilter as $k => $v) {
                                $checkData = Employee::where('kar_id', $v['kar_id'])->first();

                                if (!$checkData) {
                                    DB::beginTransaction();
                                    try {
                                        $insert = Employee::create([
                                            'kar_id' => $v['kar_id'],
                                            'employee_name' => $v['nama'],
                                            'employee_company' => $v['company']
                                        ]);

                                        EmployeeMachine::create([
                                            'employee_id' => $insert->employee_id,
                                            'kar_id' => $v['kar_id'],
                                            'msn_id' => $machine->msn_id,
                                            'cloud_id' => $machine->cloud_id,
                                        ]);

                                        DB::commit();
                                    } catch (\Exception $th) {
                                        DB::rollBack();
                                    }
                                } else {
                                    $employeemachine = EmployeeMachine::where([
                                        'employee_id' => $checkData->employee_id,
                                        'kar_id' => $checkData->kar_id,
                                        'msn_id' => $machine->msn_id,
                                        'cloud_id' => $machine->cloud_id
                                    ])->first();

                                    if (!$employeemachine) {
                                        DB::beginTransaction();

                                        try {
                                            EmployeeMachine::create([
                                                'employee_id' => $checkData->employee_id,
                                                'kar_id' => $checkData->kar_id,
                                                'msn_id' => $machine->msn_id,
                                                'cloud_id' => $machine->cloud_id
                                            ]);

                                            DB::commit();
                                        } catch (\Exception $th) {
                                            DB::rollBack();
                                        }
                                    }
                                }
                            }
                        }

                        if ($employeeSinterFilter) {
                            foreach ($employeeSinterFilter as $k => $v) {
                                $checkData = Employee::where('kar_id', $v['kar_id'])->first();

                                if (!$checkData) {
                                    DB::beginTransaction();
                                    try {
                                        $insert = Employee::create([
                                            'kar_id' => $v['kar_id'],
                                            'employee_name' => $v['nama'],
                                            'employee_company' => $v['company']
                                        ]);

                                        EmployeeMachine::create([
                                            'employee_id' => $insert->employee_id,
                                            'kar_id' => $v['kar_id'],
                                            'msn_id' => $machine->msn_id,
                                            'cloud_id' => $machine->cloud_id,
                                        ]);

                                        DB::commit();
                                    } catch (\Exception $th) {
                                        DB::rollBack();
                                    }
                                } else {
                                    $employeemachine = EmployeeMachine::where([
                                        'employee_id' => $checkData->employee_id,
                                        'kar_id' => $checkData->kar_id,
                                        'msn_id' => $machine->msn_id,
                                        'cloud_id' => $machine->cloud_id
                                    ])->first();

                                    if (!$employeemachine) {
                                        DB::beginTransaction();

                                        try {
                                            EmployeeMachine::create([
                                                'employee_id' => $checkData->employee_id,
                                                'kar_id' => $checkData->kar_id,
                                                'msn_id' => $machine->msn_id,
                                                'cloud_id' => $machine->cloud_id
                                            ]);

                                            DB::commit();
                                        } catch (\Exception $th) {
                                            DB::rollBack();
                                        }
                                    }
                                }
                            }
                        }

                        return response()->json([
                            'status' => $status->getOriginalContent(),
                            'message' => 'Data tersimpan di logs'
                        ], 200);
                    } catch (\Exception $e) {
                        return response()->json([
                            'status' => $status->getOriginalContent(),
                            'message' => $e->getMessage()
                        ], 500);
                    }
                }
            } else {
                return response()->json([
                    'status' => $status->getOriginalContent(),
                    'message' => 'Data tersimpan di logs'
                ], 200);
            }
        } else {
            // return 'tes';
            return response()->json([
                'status' => $status->getOriginalContent(),
                'message' => 'Data tidak valid'
            ], 400);
        }
    }

    public function cron($id, $day)
    {
        $connectionResponse = [];

        try {
            $response = Http::timeout(3)->get($this->ip1);

            $connectionResponse = [
                'status' => 'true',
                'ip' => $this->ip1,
                'desc' => 'utama',
                'messages' => $response->status(),
            ];

            $this->loginUrl = "{$this->ip1}/{$this->loginUrl}";
            $this->employeesUrl = "{$this->ip1}/{$this->employeesUrl}";
            $this->attendanceUrl = "{$this->ip1}/{$this->attendanceUrl}";
            $this->employeesShiftUrl = "{$this->ip1}/{$this->employeesShiftUrl}";
            $this->getAllLog = "{$this->ip1}/{$this->getAllLog}";
        } catch (\Exception $e) {
            $connectionResponse = [
                'status' => 'false',
                'ip' => $this->ip1,
                'desc' => 'utama',
                'messages' => $e->getMessage(),
            ];
        }

        if ($connectionResponse['status'] == 'false') {
            try {
                $response = Http::timeout(3)->get($this->ip2);

                $connectionResponse = [
                    'status' => 'true',
                    'ip' => $this->ip2,
                    'desc' => 'backup',
                    'messages' => $response->status(),
                ];

                $this->loginUrl = "{$this->ip2}/{$this->loginUrl}";
                $this->employeesUrl = "{$this->ip2}/{$this->employeesUrl}";
                $this->attendanceUrl = "{$this->ip2}/{$this->attendanceUrl}";
                $this->employeesShiftUrl = "{$this->ip2}/{$this->employeesShiftUrl}";
                $this->getAllLog = "{$this->ip2}/{$this->getAllLog}";
            } catch (\Exception $e) {
                $connectionResponse = [
                    'status' => 'false',
                    'ip' => $this->ip2,
                    'desc' => 'backup',
                    'messages' => $e->getMessage(),
                    'messages' => '2 isp down!!'
                ];
            }
        }

        $dataLogin = [
            'username' => env('API_USERNAME'),
            'password' => env('API_PASSWORD')
        ];

        $responseLogin = Http::post($this->loginUrl, $dataLogin);
        $responseLogin = $responseLogin->json();

        try {
            $responseLogin = Http::post($this->loginUrl, $dataLogin);
            $responseLogin = $responseLogin->json();
            $this->JWTTOKEN = $responseLogin['token'];
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'messages' => $e->getMessage()
            ], 400);
        }

        // processing
        $shift2 = [5, 6, 7, 8];
        $data = '';
        $filename = "cron_{$this->date}.txt";
        if (Storage::exists($filename)) {
            $data = Storage::get($filename);
        }

        $currentDate = date('Y-m-d');
        if ($day == 'yesterday') {
            $currentDate = Carbon::parse($currentDate)->subDay();
            $currentDate = $currentDate->toDateString();
        }

        try {
            $machine = Machine::where('msn_id', $id)->first();
            $dataSend = [
                "trans_id" => "1",
                "cloud_id" => $machine->cloud_id,
                "start_date" => $currentDate,
                "end_date" => $currentDate
            ];
            $getAttLog = Http::withToken($this->JWTTOKEN)->post($this->getAllLog, $dataSend);
            $getAttLog = $getAttLog->json();
            $logData = $getAttLog['data']['data'] ?? [];

            if ($logData) {
                foreach ($logData as $k => $v) {
                    $karyawan_id = $v['pin'];
                    $tglabsen = explode(' ', $v['scan_date'])[0];
                    $tglShift = $tglabsen;
                    $jam = explode(' ', $v['scan_date'])[1];
                    $status = $this->desc[$v['status_scan']];

                    if (preg_match('/sinar terang/i', $machine->msn_name)) {
                        if (substr($karyawan_id, 0, 2) !== '80') {
                            $karyawan_id = "80{$karyawan_id}";
                        }
                    }

                    $tglShift = $tglabsen;
                    if ($status == 'pulang' && in_array(substr($jam, 0, 2), $shift2)) {
                        $tglShift = Carbon::parse($tglabsen)->subDay();
                        $tglShift = $tglShift->toDateString();
                    }

                    $dataSend = [
                        "kar_id" => $karyawan_id,
                        "company" => $machine->company,
                        'raw' => [
                            "drtgl <= '{$tglShift}'",
                            "ketgl >= '{$tglShift}'"
                        ]
                    ];

                    $employeeShift = Http::withToken($this->JWTTOKEN)->post($this->employeesShiftUrl, $dataSend);
                    $employeeShift = $employeeShift->json();
                    $employeeShiftData = $employeeShift['data'] ? (object) $employeeShift['data'][0] : [];

                    $employee = Employee::where('kar_id', $karyawan_id)->first();

                    if ($employee) {
                        $checkData = Attendance::where([
                            'tgl_absen' => "{$tglabsen}",
                            'status' => "{$status}",
                            'karyawan_id' => "{$karyawan_id}",
                            'company' => "{$machine->company}"
                        ])->first();


                        if (!$checkData) {
                            Attendance::create([
                                "tgl_absen" => $tglabsen,
                                "jam" => $jam,
                                "status" => $status,
                                "karyawan_id" => $karyawan_id,
                                "karyawan_name" => $employee->employee_name,
                                "cloud_id" => $machine->cloud_id,
                                "company" => $machine->company,
                                'create_date' => date('Y-m-d H:i:s'),
                                'validation' => '1',
                                'verification_method' => $this->verify[$v['verify']]
                            ]);
                        } else {
                            Attendance::where([
                                "tgl_absen" => $tglabsen,
                                "status" => $status,
                                "karyawan_id" => $karyawan_id,
                                "company" => $machine->company,
                            ])->update([
                                "jam" => $jam,
                                "cloud_id" => $machine->cloud_id
                            ]);
                        }

                        if ($employeeShiftData) {
                            if ($status == 'pulang' && in_array(substr($jam, 0, 2), $shift2)) {
                                if ($employeeShiftData->id_shift == 'Shift 1') {
                                    $tglShift = $tglabsen;
                                }
                            }
                            
                            $dataInsert = [
                                'tgl_absen' => $tglShift,
                                'jam' => $jam,
                                'status' => $this->desc[$v['status_scan']],
                                'karyawan_id' => $karyawan_id,
                                'karyawan_name' => $employeeShiftData->nama,
                                'cloud_id' => $machine->cloud_id,
                                'company' => $machine->company,
                                'create_date' => date('Y-m-d H:i:s'),
                                'validation' => '1',
                                'verification_method' => $this->verify[$v['verify']]
                            ];

                            Http::withToken($this->JWTTOKEN)->post($this->attendanceUrl, $dataInsert);
                        }
                    }
                }
            }

            $res = [
                'status' => true,
                'cloud_id' => $machine->cloud_id,
                'data' => $getAttLog['data']
            ];

            $data .= "\n" . json_encode($res);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'messages' => $e->getMessage()
            ], 400);
        }

        try {
            Storage::put($filename, $data);

            return response('OK', 200);
        } catch (\Exception $e) {
            return response('FAIL', 400);
        }
    }
}
