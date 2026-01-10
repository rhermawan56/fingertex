<?php

namespace App\Models;

use App\Models\Machine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashabsensi extends Model
{
    use HasFactory;
    protected $table = 'absensi';
    protected $guarded = ['id'];
    const CREATED_AT = 'create_date';
    public $timestamps = true;
    const UPDATED_AT = null;
    private static $JWTTOKEN = '';
    private static $loginUrl = "http://103.76.15.27/webhook_api/api/login";
    private static $getAllPinUrl = "http://103.76.15.27/webhook_api/api/get_all_pin";
    private static $getEmployeeUrl = "http://103.76.15.27/webhook_api/api/get_employees";
    private static $getUser = "http://103.76.15.27/webhook_api/api/get_userinfo";
    private static $setUser = "http://103.76.15.27/webhook_api/api/set_userinfo";
    private static $deleteUser = "http://103.76.15.27/webhook_api/api/delete_userinfo";
    private static $getlog = "http://103.76.15.27/webhook_api/api/get_attlog";
    private static $attendanceUrl = "http://103.76.15.27/webhook_api/api/attendance_insert";

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

        $data = Dashabsensi::query()
            ->join('ms_mesin', 'ms_mesin.cloud_id', '=', 'absensi.cloud_id');


        $columns = function () use ($request) {
            $col = collect($request->input('columns'));
            return $col->filter(function ($item) {
                return $item['search']['value'];
            });
        };

        $order = function () use ($request) {
            $order = null;
            $ord = collect($request->input('order')[0] ?? []);

            if ($ord && isset($ord['column'])) {
                if ($ord['column'] > 0) {
                    $order = [
                        'column' => $request->input('columns')[$ord['column']]['data'],
                        'dir' => $ord['dir']
                    ];
                }
            }

            return $order;
        };

        // === 🧠 FILTER KOLOM ===
        if (count($columns()) > 0) {
            foreach ($columns() as $k => $v) {
                $colName = $v['data'];
                $val = trim($v['search']['value']);

                // 🗓 khusus kolom tanggal (index 1)
                if ($colName === 'tgl_absen') {
                    // format: 2025-11-01_2025-11-06 atau 2025-11-01 s.d 2025-11-06
                    $val = str_replace(' s.d ', '_', $val);
                    $dates = explode('_', $val);

                    if (count($dates) === 2) {
                        $start = $dates[0];
                        $end = $dates[1];
                    } else {
                        $start = Carbon::today()->format('Y-m-d');
                        $end = Carbon::today()->format('Y-m-d');
                    }

                    $data = $data->whereBetween($colName, [$start, $end]);
                } else {
                    // default: pencarian LIKE biasa
                    $data = $data->where($colName, 'like', "%{$val}%");
                }
            }
        } else {
            // 🗓 jika tidak ada filter sama sekali, default tanggal = hari ini
            $today = Carbon::today()->format('Y-m-d');
            $data = $data->whereBetween('tgl_absen', [$today, $today]);
        }

        // === 🧾 ORDER BY ===
        if ($order()) {
            $data = $data->orderBy($order()['column'], $order()['dir']);
        }

        // === 🔄 PAGINATION ===
        $data = $data->select('absensi.*', 'ms_mesin.msn_name');
        $response['rows'] = clone $data;
        $response['data'] = $data->offset($request->input('start'))->limit($request->input('length'))->get();
        $response['rows'] = $response['rows']->count();

        return $response;
    }
}
