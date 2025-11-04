<?php

namespace App\Models;

// use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    private static $JWTTOKEN = '';

    public static function loginApi()
    {
        $url = "http://103.76.15.27/webhook_api/api/login";

        try {
            $response = Http::post(
                $url,
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
        $url = "http://103.76.15.27/webhook_api/api/get_all_pin";
        $now = date('Y_m_d');
        $filename = "get_userid_list_{$now}.txt";

        try {

            foreach ($data as $k => $v) {
                $dataSend = [
                    "trans_id" => $k + 1,
                    "cloud_id" => $v->cloud_id
                ];

                $response = Http::withToken(self::$JWTTOKEN)->post($url, $dataSend);
                $response = $response->json();
            }

            $maxWait = 10;
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

            $dataSend = [
                'wherein' => [
                    [
                        'field' => 'kar_id',
                        'values' => $pin
                    ]
                ],
                "raw" => $raw
            ];

            $url = "http://103.76.15.27/webhook_api/api/get_employees";
            $requestEmployee = Http::withToken(self::$JWTTOKEN)->post($url, $dataSend);
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
                'messages' => 'success'
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
