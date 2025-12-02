<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Attendance;
use App\Models\Machine;

class WebhookController extends BaseController
{
    private $date;
    private $JWTTOKEN = 0;
    private $ip1 = 'http://103.76.15.27';
    private $ip2 = 'http://119.18.157.213';

    private $loginUrl = "webhook_api/api/login";
    private $employeesUrl = "webhook_api/api/get_employees";
    private $attendanceUrl = "webhook_api/api/attendance_insert";
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
            if ($data->type == 'attlog') {
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

                try {
                    $machine = Machine::where('cloud_id', $data->cloud_id)->first();
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

                    $check = Attendance::where(['karyawan_id' => $responseData['kar_id'], 'tgl_absen' => explode(' ', $data->data->scan)[0], 'status' => $this->desc[$data->data->status_scan]])->count();

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

                    if ($check == 0) {
                        Attendance::insert($dataInsert);
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
}
