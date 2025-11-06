<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Machine extends Model
{
    use HasFactory;
    protected $table = 'ms_mesin';
    protected $primaryKey = 'msn_id';
    protected $guarded = ['msn_id'];
    const CREATED_AT = 'msn_creation';
    public $timestamps = true;
    const UPDATED_AT = null;

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
}
