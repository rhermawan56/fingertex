<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Machine;
use App\Models\Role;
use App\Models\Sub_menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class EmployeeController extends Controller
{
    private $roleId, $role, $submenu, $routes;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
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
            'js' => 'employee'
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
        $employee = Employee::getDataEmployee($id, $machine)->getOriginalContent();

        $data = [
            'data' => (object) $employee['data'],
            'machine' => $machine,
            'js' => 'employee'
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
            'nama' => 'required',
            'removemachine' => ['nullable', 'array'],
            'removemachine.*' => ['string', 'exists:ms_mesin,cloud_id'],
            'addmachine' => ['nullable', 'array'],
            'addmachine.*' => ['string', 'exists:ms_mesin,cloud_id']
        ];

        $messages = [
            'kar_id.required' => 'This field is required!',
            'nama.required' => 'This field is required!',
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

        $machine = Machine::all();
        $data = Employee::getDataEmployees($request, $machine)->getOriginalContent();
        // $data = Employee::getDataEmployees($request, $machine);
        // return $data;

        return response()->json([
            // 'status' => $data['status'],
            // 'messages' => $data['messages'],
            'draw' => $request['draw'],
            'recordsTotal' => $data['rows'],
            'recordsFiltered' => $data['rows'],
            'data' => $data['data'],
            'permission' => $roleaccess,
            'token' => csrf_token(),
            'start' => $request->input('start'),
            'messages' => $data['messages']
        ], 200);
    }
}
