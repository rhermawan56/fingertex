<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Machine;
use App\Models\Sub_menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MachineController extends Controller
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
            'js' => 'machine'
        ];

        return view('machine.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data = [
            'js' => 'machine'
        ];

        return view('machine.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules = [
            'company' => 'required',
            'msn_type' => 'required',
            'cloud_id' => 'required|unique:ms_mesin,cloud_id',
            'msn_name' => 'required'
        ];

        $messages = [
            'company.required' => 'This field is required!',
            'msn_type.required' => 'This field is required!',
            'cloud_id.required' => 'This field is required!',
            'cloud_id.unique' => 'Cloud Id already in use!',
            'msn_name.required' => 'This field is required!',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // dd($request->all());

        DB::beginTransaction();

        try {
            Machine::create($request->all());
            DB::commit();
            return redirect()->route('machine.index')
                ->with('success', 'The machine data has been successfully saved!');
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
            // return redirect()->route('machine.index')
            //     ->with('error', 'The machine data could not be saved!');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Machine  $machine
     * @return \Illuminate\Http\Response
     */
    public function show(Machine $machine)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Machine  $machine
     * @return \Illuminate\Http\Response
     */
    public function edit(Machine $machine)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Machine  $machine
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Machine $machine)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Machine  $machine
     * @return \Illuminate\Http\Response
     */
    public function destroy(Machine $machine)
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

        $data = Machine::fetchdata($request);

        return response()->json([
            'draw' => $request['draw'],
            'recordsTotal' => $data['rows'],
            'recordsFiltered' => $data['rows'],
            'data' => $data['data'],
            'token' => csrf_token(),
            'permission' => $roleaccess,
            // 'request' => $roleaccess
        ], 200);
    }
}
