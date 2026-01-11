<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GlobalServices
{
    private $ArrayIp;
    private $date;
    private $username;
    private $password;
    private $API_TOKEN;
    private $urlfs = "https://developer.fingerspot.io/api";
    private $userinfo = "get_userinfo";
    public $attlog = "get_attlog";

    public $loginUrl = "webhook_api/api/login";
    public $employeelocal = "webhook_api/api/get_employees";
    public $localattendance = "webhook_api/api/attendance_insert";
    public $employeeshift = "webhook_api/api/shift";

    public $desc = [
        "0" => 'masuk',
        "1" => 'pulang',
        "2" => 'istirahat',
        "3" => 'masuk istirahat',
        "4" => 'masuk lembur',
        "5" => 'pulang lembur',
        "6" => 'masuk rapat',
        "7" => 'keluar rapat',
    ];

    public $verify = [
        "1" => "finger",
        "2" => "password",
        "3" => "card",
        "4" => "face",
        "6" => "vein",
        "7" => "QR",
    ];

    public function __construct()
    {
        $this->ArrayIp = [config('app.IP_ONE'), config('app.IP_TWO'), config('app.IP_THREE')];
        $this->date = date('Y_m_d');
        $this->username = config('app.API_USERNAME');
        $this->password = config('app.API_PASSWORD');
        $this->API_TOKEN = config('app.API_TOKEN');
    }

    public function connection()
    {
        $connectionLoop = 3;
        $messages = 'Api Connection Succes';

        for ($i = 0; $i < $connectionLoop; $i++) {
            try {
                $this->checkConnection($this->ArrayIp[$i]);

                return response()->json([
                    'status' => true,
                    'messages' => 'Api Connection Succes',
                    'ip' => $this->ArrayIp[$i]
                ], 200);
            } catch (\Exception $e) {
                $messages = $e->getMessage();
            }
        }


        return response()->json([
            'status' => false,
            'messages' => $messages
        ], 400);
    }

    public function checkConnection($IP)
    {
        return Http::timeout(3)->get($IP);
    }

    public function savelog($body, $jsondata, $name)
    {
        $filename = "{$name}_{$this->date}.txt";

        $data = '';
        if (Storage::exists($filename)) {
            $data = Storage::get($filename);
        }

        $data .= $body . "\n";

        try {
            Storage::put($filename, $data);

            return response()->json([
                'status' => true,
                'messages' => 'OK'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'messages' => $e->getMessage()
            ], 400);
        }
    }

    public function login($IP)
    {
        $url = "{$IP}/{$this->loginUrl}";
        $dataLogin = [
            "username" => $this->username,
            "password" => $this->password
        ];

        try {
            $login = Http::post($url, $dataLogin);

            if (!$login['status']) {
                return response()->json([
                    'status' => false,
                    'messages' => 'Login Failed',
                    'data' => $login,
                ], 400);
            }

            return response()->json([
                'status' => true,
                'messages' => 'Login Success',
                'data' => $login,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'messages' => $e->getMessage(),
                'data' => [],
            ], 400);
        }
    }

    public function employeelocal($IP, $PIN, $COMPANY, $TOKEN)
    {
        $url = "{$IP}/{$this->employeelocal}";

        $dataSend = [
            "company" => $COMPANY,
            "kar_id" => $PIN
        ];

        return Http::withToken($TOKEN)->post($url, $dataSend);
    }

    public function allemployeelocal($IP, $PIN, $COMPANY, $TOKEN)
    {
        $url = "{$IP}/{$this->employeelocal}";

        $dataSend = [
            "company" => $COMPANY,
            "wherein" => [
                [
                    'field' => 'kar_id',
                    'values' => $PIN
                ]
            ],
            "select" =>  ['kar_id', 'nama', 'group'],
            "start"  => 0,
            "length" => 1000
        ];

        return Http::withToken($TOKEN)->post($url, $dataSend);
    }

    public function employeeshift($IP, $GROUP, $COMPANY, $DATE, $TOKEN) {
        $url = "{$IP}/{$this->employeeshift}";
        $now = $DATE;
        $yesterday = Carbon::parse($now)->subDay()->toDateString();

        $dataSend = [
            'company' => $COMPANY,
            'wherein' => [
                [
                    'field' => 'id_group',
                    'values' => $GROUP
                ]
            ],
            'raw' => [
                "(drtgl <= '{$yesterday}' AND ketgl >= '{$yesterday}') OR (drtgl <= '{$now}' AND ketgl >= '{$now}')",
            ]
        ];

        return Http::withToken($TOKEN)->post($url, $dataSend);
    }

    public function userinfo($trans_id, $pin, $machine)
    {
        $url = "{$this->urlfs}/{$this->userinfo}";
        $dataSend = [
            'trans_id' => $trans_id,
            'cloud_id' => $machine->cloud_id,
            'pin' => $pin
        ];

        return Http::withToken($this->API_TOKEN)->post($url, $dataSend);
    }

    public function attlog($IP, $MACHINE, $DATE)
    {
        $url = "{$this->urlfs}/{$this->attlog}";
        $dataSend = [
            "trans_id" => "1",
            "cloud_id" => $MACHINE->cloud_id,
            "start_date" => $DATE,
            "end_date" => $DATE,
        ];

        return Http::withToken($this->API_TOKEN)->post($url, $dataSend);
    }

    public function localattlog($IP, $MACHINE, $TOKEN, $data)
    {
        $url = "{$IP}/{$this->localattendance}";

        $dataSend = [
            "tgl_absen" => $data->tgl_shift,
            "jam" => $data->jam,
            "status" => $data->status,
            "karyawan_id" => $data->karyawan_id,
            "karyawan_name" => $data->karyawan_name,
            "cloud_id" => $data->cloud_id,
            "company" => $data->company,
            "create_date" => date('Y-m-d H:i:s'),
            "validation" => $data->validation,
            "verification_method" => $data->verification_method
        ];

        return Http::withToken($TOKEN)->post($url, $dataSend);
    }
}
