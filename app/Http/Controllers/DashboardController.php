<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Machine;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

class DashboardController extends Controller
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
        $request = new Request();
        $machine = Machine::all();
        $employees = Employee::getDataEmployees($request, $machine)->getOriginalContent();
        $attendaces = Attendance::where('tgl_absen', date('Y-m-d'))
            ->select('absensi.*')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('absensi')
                    ->groupBy('karyawan_id');
            })
            ->get();

        $data = [
            'employees' => (object) $employees,
            'attendance' => $attendaces,
            'js' => 'dashboard'
        ];
        return view('dashboard', $data);
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
