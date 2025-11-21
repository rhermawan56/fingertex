<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Dashabsensi extends Model
{
    use HasFactory;
    protected $table = 'absensi';
    // protected $guarded = ['id'];
    const CREATED_AT = 'create_date';
    public $timestamps = true;
    const UPDATED_AT = null;

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
