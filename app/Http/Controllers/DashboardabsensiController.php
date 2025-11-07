<?php

namespace App\Http\Controllers\Absensi;


use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Dashabsensi;
use App\Models\Sub_menu;
use Illuminate\Support\Facades\Auth;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use App\Http\Controllers\Controller;

class DashboardabsensiController extends Controller
{
    private $role, $menu, $submenu;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $roleId = Auth::user()->role_id;
            $this->role = Role::where('id', $roleId)->first();
            $this->menu = $this->role->role_access->filter(function ($item) {
                return $item['menu_id'];
            })->values();
            $this->submenu = $this->role->role_access->filter(function ($item) {
                return $item['submenu_id'];
            })->values();
            return $next($request);
        });
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = [
            'js' => 'dashboardabsensi'
        ];
        return view('absensi.dashboard', $data);
    }

     public function fetchdata(Request $request)
    {
        $this->routes = implode('/', array_slice(request()->segments(), 0, -1));
        $submenu = Sub_menu::where('url', "/{$this->routes}")->first();
        $roleaccess = $this->role->role_access->filter(function ($item) use ($submenu) {
            return $item['submenu_id'] == $submenu->id;
        })->values();

        $data = Dashabsensi::fetchdata($request);

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
        //
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
        //
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
}
