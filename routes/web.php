<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
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
    Route::get('employee', [EmployeeController::class, 'index'])->name('employee');
    Route::post('employee/fetchdata', [EmployeeController::class, 'fetchdata'])->name('employee');
});

Route::get('/test', function () {
    $hash = '$2y$10$XcXcpzvK0KhZ8Fdl6pDaQ.5PIsUeLE5A1PpGzR5g9x8A9vJzFPp6a';
    dd(Hash::check('admin123', $hash));
});
