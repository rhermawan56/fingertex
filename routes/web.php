<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\Absensi\DashboardabsensiController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return redirect()->route('login');
});


Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login.submit');
});


Route::post('logout', [LoginController::class, 'logout'])->name('logout');
Route::get('logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/updatpassword', [LoginController::class, 'updatpassword'])->name('updatpassword');


Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('employees/employee', EmployeeController::class)->except(['fetchdata', 'create'])->names('employee');
    Route::post('employees/employee/fetchdata', [EmployeeController::class, 'fetchdata'])->name('employee.fetchdata');
    Route::get('absensi/log', [DashboardabsensiController::class, 'index'])->name('historyabsen');
    Route::post('absensi/log/fetchdata', [DashboardabsensiController::class, 'fetchdata'])->name('fetchdata');
    Route::resource('absensi/mesinfinger', MachineController::class)->except(['fetchdata', 'resettime', 'restartmachine'])->names('machine');
    Route::post('absensi/mesinfinger/fetchdata', [MachineController::class, 'fetchdata'])->name('machine.fetchdata');
    Route::post('absensi/mesinfinger/resettime', [MachineController::class, 'resettime'])->name('machine.resettime');
    Route::post('absensi/mesinfinger/restartmachine', [MachineController::class, 'restartmachine'])->name('machine.restartmachine');
});

Route::get('/test', function () {
    $hash = '$2y$10$XcXcpzvK0KhZ8Fdl6pDaQ.5PIsUeLE5A1PpGzR5g9x8A9vJzFPp6a';
    dd(Hash::check('admin123', $hash));
});
