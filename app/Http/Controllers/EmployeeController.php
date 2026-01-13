<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Machine;
use App\Models\Role;
use App\Models\Sub_menu;
use App\Services\GlobalServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class EmployeeController extends Controller
{
    private $roleId, $role, $submenu, $routes, $global;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct(GlobalServices $global)
    {
        $this->global = $global;

        $this->middleware(function ($request, $next) {
            $this->roleId = Auth::user()->role_id;
            $this->role = Role::where('id', $this->roleId)->first();
            return $next($request);
        });
    }

    public function index()
    {
        $data = [
            'title' => 'tes',
            'js' => 'employeev3'
        ];
        return view('employee.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $machine = Machine::all();
        $employee = Employee::where('kar_id', $id)->first();
        if ($employee) {
            $cloudId = [];
            foreach ($employee->employee_machines as $k => $v) {
                $cloudId[] = $v->cloud_id;
            }

            $employee['cloud_id'] = collect($cloudId);
        }

        $data = [
            'data' => $employee,
            'machine' => $machine,
            'js' => 'employeev3'
        ];

        return view('employee.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $rules = [
            'kar_id' => 'required',
            'employee_name' => 'required',
            'removemachine' => ['nullable', 'array'],
            'removemachine.*' => ['string', 'exists:ms_mesin,cloud_id'],
            'addmachine' => ['nullable', 'array'],
            'addmachine.*' => ['string', 'exists:ms_mesin,cloud_id']
        ];

        $messages = [
            'kar_id.required' => 'This field is required!',
            'employee_name.required' => 'This field is required!',
        ];

        if (!$request->addmachine && !$request->removemachine) {
            if (!$request->removemachine) {
                $rules['removemachine'] = ['required', 'array', 'min:1'];
            }

            if (!$request->addmachine) {
                $rules['addmachine'] = ['required', 'array', 'min:1'];
            }
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $processedData = Employee::processData($request)->getOriginalContent();
        $processedData = (object) $processedData;

        if ($processedData->status) {
            return redirect()->route('employee.index', $request->kar_id)
                ->with('success', $processedData->messages);
        } else {
            return redirect()->route('employee.index', $request->kar_id)
                ->with('error', $processedData->messages);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function fetchdata(Request $request)
    {
        $this->routes = implode('/', array_slice(request()->segments(), 0, -1));
        $submenu = Sub_menu::where('url', "/{$this->routes}")->first();
        $roleaccess = $this->role->role_access->filter(function ($item) use ($submenu) {
            return $item['submenu_id'] == $submenu->id;
        })->values();

        $newData = [];
        $data = Employee::getDataEmployees($request)->getOriginalContent();

        foreach ($data['data'] as $k => $v) {
            $d = $v->toArray();
            $d['machine'] = collect($v->employee_machines)->map(function ($item) {
                $item['msn_name'] = $item->machine->msn_name;
                return $item;
            })->toArray();

            $newData[] = $d;
        }


        $columns = function () use ($request) {
            return collect($request->columns)->filter(function ($item) {
                return $item['search']['value'];
            })->toArray();
        };

        // $teslagi = collect($newData[0]['machine'])->filter(function ($item) {
        //     return preg_match('/knitting/i', $item['msn_name']);
        // })->toArray();

        // dd($teslagi);

        // $tes = collect($newData)->filter(function ($item) {
        //     return collect($item['machine'])->filter(function ($it) {
        //         return preg_match('/knitting/i', $it['msn_name']);
        //     })->toArray();
        // })->values()->all();

        if ($columns()) {
            foreach ($columns() as $k => $v) {
                $after = [];
                $before = [];
                $none = [];
                $includeFilter = ['machine', 'cloud_id'];

                if ($v['data'] == 'machine') {
                    $newData = collect($newData)->filter(function ($item) use ($v) {
                        return collect($item['machine'])->filter(function ($it) use ($v) {
                            return preg_match('/' . preg_quote($v['search']['value'], '/') . '/i', $it['msn_name']);
                        })->toArray();
                    })->values()->all();
                }

                if ($v['data'] == 'cloud_id') {
                    $newData = collect($newData)->filter(function ($item) use ($v) {
                        return collect($item['machine'])->filter(function ($it) use ($v) {
                            return preg_match('/' . preg_quote($v['search']['value'], '/') . '/i', $it['cloud_id']);
                        })->toArray();
                    })->values()->all();
                }
            }
        }

        // $data = Employee::getDataEmployees($request, $machine);
        // return $data;

        return response()->json([
            // 'status' => $data['status'],
            // 'messages' => $data['messages'],
            'draw' => $request['draw'],
            'recordsTotal' => $data['rows'],
            'recordsFiltered' => $data['rows'],
            'data' => $newData,
            'permission' => $roleaccess,
            'token' => csrf_token(),
            'start' => $request->input('start'),
            'messages' => $data['messages']
        ], 200);
    }

    public function syncdata(Request $request)
    {
        $sync = [];
        $machine = Machine::where([
            'msn_status' => '1'
        ])
        ->get();

        if ($machine) {
            foreach ($machine as $k => $v) {
                $this->global->fnallpin($k + 1, $v->cloud_id);
            }
        }

        return response()->json([
            'status' => true,
            'data' => $sync,
            // 'token' => csrf_token()
        ], 200);
    }
}
