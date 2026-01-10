<?php

namespace App\Http\Controllers;

use App\Jobs\HitApiJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Attendance;
use App\Models\Dashabsensi;
use App\Models\Employee;
use App\Models\EmployeeMachine;
use App\Models\Machine;
use App\Services\GlobalServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

class WebhookController extends BaseController
{
    private $date;
    private $ip;
    private $JWT_KEY;

    protected GlobalServices $global;

    public function __construct(GlobalServices $global)
    {
        $this->global = $global;
        $this->date = date('Y_m_d');
    }

    private function isValidDate($date): bool
    {
        try {
            Carbon::createFromFormat('Y-m-d', $date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkConnection()
    {
        $connection = $this->global->connection();
        $connection = (object) $connection->getOriginalContent();

        if (!$connection->status) {
            return response()->json([
                'status' => $connection->status,
                'messages' => "Connection Failed"
            ], 400);
        }

        $this->ip = $connection->ip;
    }

    private function login($IP)
    {
        $login = $this->global->login($IP);
        $login = (object) $login->getOriginalContent();

        if (!$login->status) {
            return response()->json([
                'status' => $login->status,
                'messages' => "Login Failed"
            ], 400);
        }

        $this->JWT_KEY = $login->data['token'];
    }

    public function receive(Request $request)
    {
        $status = 'FAIL';
        $body = $request->getContent();

        $jsonData = $body . "\n";
        $jsonData = json_decode($jsonData);
        $data = (object) $jsonData;

        $log = $this->global->savelog($body, $jsonData, $jsonData->type);
        $log = (object) $log->getOriginalContent();

        if ($data->type == 'attlog') {
            $processData = $this->attlog($data);
            $processData = (object) $processData->getOriginalContent();

            if (!$processData->status) {
                return response()->json([
                    'status' => $processData->status,
                    'messages' => $processData->messages
                ], $processData->status_response);
            }

            return response()->json([
                'status' => $processData->status,
                'messages' => $processData->messages
            ], $processData->status_response);
        }

        if ($data->type == 'get_userid_list') {
            $processData = $this->userlist($data);
            $processData = (object) $processData->getOriginalContent();

            if (!$processData->status) {
                return response()->json([
                    'status' => $processData->status,
                    'messages' => $processData->messages
                ], $processData->status_response);
            }

            return response()->json([
                'status' => $processData->status,
                'messages' => $processData->messages
            ], $processData->status_response);
        }

        if ($data->type == 'get_userinfo') {
            $processData = $this->userinfo($data);
            $processData = (object) $processData->getOriginalContent();

            if (!$processData->status) {
                return response()->json([
                    'status' => $processData->status,
                    'messages' => $processData->messages
                ], $processData->status_response);
            }

            return response()->json([
                'status' => $processData->status,
                'messages' => $processData->messages
            ], $processData->status_response);
        }
    }

    private function attlog($data)
    {
        $attendanceDateTime = explode(' ', $data->data->scan);
        $attendanceDate = $attendanceDateTime[0];
        $attendanceTime = $attendanceDateTime[1];

        $machine = Machine::where([
            'msn_status' => '1',
            'cloud_id' => $data->cloud_id
        ])->first();

        if (!$machine) {
            return response()->json([
                'status' => false,
                'messages' => 'Machine not Found',
                'status_response' => 404
            ], 404);
        }

        $employee = Employee::where([
            'kar_id' => $data->data->pin
        ])->first();

        if (!$employee) {
            return response()->json([
                'status' => false,
                'messages' => 'Employee not found',
                'status_response' => 404
            ], 404);
        }

        $status = $this->global->desc[$data->data->status_scan];
        $verification_method = $this->global->verify[$data->data->verify];

        $attendance = Dashabsensi::where([
            'karyawan_id' => $data->data->pin,
            'status' => $status,
            'tgl_absen' => $attendanceDate
            // 'tgl_absen' => '2025-12-23'
        ])->first();

        if (!$attendance) {
            Dashabsensi::create([
                "tgl_absen" => $attendanceDate,
                "jam" => $attendanceTime,
                "status" => $status,
                "karyawan_id" => $data->data->pin,
                "karyawan_name" => $employee->employee_name,
                "cloud_id" => $machine->cloud_id,
                "company" => $machine->company,
                "create_date" => date('Y-m-d H:i:s'),
                "validation" => '1',
                "verification_method" => $verification_method
            ]);
        } else {
            Dashabsensi::where([
                'karyawan_id' => $data->data->pin,
                'status' => $status,
                'tgl_absen' => $attendanceDate
            ])->update([
                "jam" => $attendanceTime,
                "status" => $status,
                "create_date" => date('Y-m-d H:i:s'),
                "validation" => '1',
                "verification_method" => $verification_method
            ]);
        }

        $employeemachine = EmployeeMachine::where([
            'kar_id' => $data->data->pin,
            'msn_id' => $machine->msn_id,
            'cloud_id' => $machine->cloud_id
        ])->first();

        if (!$employeemachine) {
            EmployeeMachine::create([
                "employee_id" => $employee->employee_id,
                "kar_id" => $data->data->pin,
                "msn_id" => $machine->msn_id,
                "cloud_id" => $machine->cloud_id,
                "em_creation" => date('Y-m-d H:i:s')
            ]);
        }

        return response()->json([
            'status' => true,
            'messages' => 'Success',
            'status_response' => 200
        ], 200);
    }

    private function userlist($data)
    {
        $pin = $data->data->pin_arr;

        $machine = Machine::where([
            'msn_status' => '1',
            'cloud_id' => $data->cloud_id
        ])->first();

        if (!$machine) {
            return response()->json([
                'status' => false,
                'messages' => 'Machine not Found',
                'status_response' => 404
            ], 404);
        }

        $employee = Employee::whereIn('kar_id', $pin)->get();
        $employee = collect($employee)->pluck('kar_id')->toArray();

        $notEmployee = collect(array_diff($pin, $employee))->values()->toArray();

        if ($notEmployee) {
            foreach ($notEmployee as $k => $v) {
                $getdata = $this->global->userinfo($k + 1, $v, $machine);
                $getdata = (object) $getdata->json();

                if (!$getdata->success) {
                    $save = collect($getdata)->toArray();
                    $save['pin'] = $v;
                    $this->global->savelog(json_encode($save), $save, 'err_userinfo');
                }
            }
        }

        return response()->json([
            'status' => true,
            'messages' => 'Success',
            'status_response' => 200
        ], 200);
    }

    private function userinfo($data)
    {
        $machine = Machine::where([
            'msn_status' => '1',
            'cloud_id' => $data->cloud_id
        ])->first();

        $employee = Employee::where([
            'kar_id' => $data->data->pin
        ])->first();

        $checkCompanyUser = substr($data->data->pin, 0, 2);
        $userCompany = 'PT. KAHAPTEX';
        $karId = $data->data->pin;

        if ($checkCompanyUser == '80') {
            $userCompany = "PT.SINAR TERANG";
            $karId = substr($data->data->pin, 2);
        }

        if (!$employee) {
            $this->checkConnection();
            $this->login($this->ip);

            $employeelocal = $this->global->employeelocal($this->ip, $data->data->pin, $userCompany, $this->JWT_KEY);
            $employeelocal = (object) $employeelocal->json();

            if ($employeelocal->data) {
                DB::beginTransaction();

                try {
                    $createEmployee = Employee::create([
                        "kar_id" => $data->data->pin,
                        "employee_name" => $employeelocal->data[0]['nama'],
                        "employee_company" => $userCompany,
                        "employee_name_machine" => $data->data->name,
                        "template" => $data->data->template,
                    ]);

                    EmployeeMachine::create([
                        "employee_id" => $createEmployee->employee_id,
                        "kar_id" => $data->data->pin,
                        "msn_id" => $machine->msn_id,
                        "cloud_id" => $machine->cloud_id,
                        "em_creation" => date('Y-m-d H:i:s')
                    ]);

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                }
            };
        } else {
            if (!$employee->template) {
                Employee::where([
                    'kar_id' => $data->data->pin
                ])->update([
                    "employee_name_machine" => $data->data->name,
                    "template" => $data->data->template
                ]);
            }

            $employeemachine = EmployeeMachine::where([
                'kar_id' => $data->data->pin,
                'msn_id' => $machine->msn_id,
                'cloud_id' => $machine->cloud_id
            ])->first();

            if (!$employeemachine) {
                EmployeeMachine::create([
                    "employee_id" => $employee->employee_id,
                    "kar_id" => $data->data->pin,
                    "msn_id" => $machine->msn_id,
                    "cloud_id" => $machine->cloud_id,
                    "em_creation" => date('Y-m-d H:i:s')
                ]);
            }
        }

        return response()->json([
            'status' => true,
            'messages' => 'Success',
            'status_response' => 200
        ], 200);
    }

    public function cron($id, $day)
    {
        $currentDate = date('Y-m-d');
        $dateRequest = '';

        if ($day == 'now') {
            $dateRequest = $currentDate;
        } else if ($day == 'yesterday') {
            $dateRequest = Carbon::parse($currentDate)->subDay()->toDateString();
        } else if ($this->isValidDate($day)) {
            $dateRequest = Carbon::parse($day)->toDateString();
        } else {
            abort(400, 'Format tanggal tidak valid');
        }

        $machine = Machine::where([
            'msn_id' => $id,
            'msn_status' => '1'
        ])->first();

        if (!$machine) {
            abort(404, "Mesin tidak ditemukan!");
        }

        $data = $this->global->attlog($this->ip, $machine, $dateRequest);
        $data = (object) $data->json();

        $pin = collect($data->data)->pluck('pin')->unique()->values()->toArray();

        $attendance = Dashabsensi::where([
            'tgl_absen' => $dateRequest
        ])
            ->whereIn('karyawan_id', $pin)
            ->get();

        $employee = Employee::whereIn('kar_id', $pin)->get();

        foreach ($data->data as $k => $v) {
            $attendanceDateTime = explode(' ', $v['scan_date']);
            $attendanceDate = $attendanceDateTime[0];
            $attendanceTime = $attendanceDateTime[1];
            $verification_method = $this->global->verify[$v['verify']];
            $status = $this->global->desc[$v['status_scan']];

            $attendanceFilter = collect($attendance)->filter(function ($item) use ($v, $status) {
                return $item->karyawan_id == $v['pin'] && $item->status == $status;
            })->values()->toArray();

            $employeeFilter = collect($employee)->filter(function ($item) use ($v) {
                return $item->kar_id == $v['pin'];
            })->values()->toArray();

            if ($employeeFilter) {
                $employeeFilter = (object) $employeeFilter[0];

                if (!$attendanceFilter) {
                    Dashabsensi::create([
                        "tgl_absen" => $attendanceDate,
                        "jam" => $attendanceTime,
                        "status" => $status,
                        "karyawan_id" => $v['pin'],
                        "karyawan_name" => $employeeFilter->employee_name,
                        "cloud_id" => $machine->cloud_id,
                        "company" => $machine->company,
                        "create_date" => date('Y-m-d H:i:s'),
                        "validation" => '1',
                        "verification_method" => $verification_method
                    ]);
                } else {
                    $attendanceFilter = (object) $attendanceFilter[0];
                    Dashabsensi::where([
                        "tgl_absen" => $attendanceDate,
                        "status" => $status,
                        "karyawan_id" => $v['pin'],
                    ])->update([
                        "jam" => $attendanceTime,
                        "cloud_id" => $machine->cloud_id,
                        "company" => $machine->company,
                        "create_date" => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }

        HitApiJob::dispatch("fingertex.test/api/cronlocal/{$id}/{$dateRequest}");

        echo 'ok';
    }

    public function cronlocal($id, $day)
    {
        $currentDate = date('Y-m-d');
        $dateRequest = '';

        if ($day == 'now') {
            $dateRequest = $currentDate;
        } else if ($day == 'yesterday') {
            $dateRequest = Carbon::parse($currentDate)->subDay()->toDateString();
        } else if ($this->isValidDate($day)) {
            $dateRequest = Carbon::parse($day)->toDateString();
        } else {
            abort(400, 'Format tanggal tidak valid');
        }

        $this->checkConnection();
        $this->login($this->ip);

        $machine = Machine::where([
            'msn_id' => $id,
            'msn_status' => '1'
        ])->first();

        if (!$machine) {
            abort(404);
        }

        $attendance = Dashabsensi::where([
            'tgl_absen' => $dateRequest,
            'cloud_id' => $machine->cloud_id,
            'status_upload' => '0'
        ])->get();

        $pin = collect($attendance)->pluck('karyawan_id')->unique()->values()->toArray();
        $pin = collect($pin)->map(function ($item) {
            $item = substr($item, 0, 2) == 80 ? substr($item, 2) : $item;
            return $item;
        });

        $employeelocal = $this->global->allemployeelocal($this->ip, $pin, $machine, $this->JWT_KEY);
        $employeelocal = (object) $employeelocal->json();

        if ($attendance) {
            foreach ($attendance as $k => &$v) {
                $employeelocalFilter = collect($employeelocal->data)->filter(function ($item) use ($v) {
                    $karId = $item['kar_id'];
                    if (stripos($v->company, 'SINAR TERANG') !== false || stripos($v->company, 'SINARTERANG') !== false) {
                        $karId = "80{$item['kar_id']}";
                    }
                    return $karId == $v->karyawan_id;
                })->values()->toArray();

                if ($employeelocalFilter) {
                    $employeelocalFilter = (object) $employeelocalFilter[0];

                    $localattlog = $this->global->localattlog($this->ip, $machine, $this->JWT_KEY, $v);
                    $localattlog = $localattlog->json();

                    if ($localattlog) {

                        $localattlog = (object) $localattlog;

                        if (!$localattlog->data) {
                            $save = collect($localattlog)->toArray();
                            $save['karyawan_id'] = $v->karyawan_id;
                            $save['karyawan'] = $v->karyawan_name;

                            $this->global->savelog(json_encode($save), $save, 'err_local_attlog');
                        } else {
                            Dashabsensi::where([
                                "tgl_absen" => $v->tgl_absen,
                                "status" => $v->status,
                                "karyawan_id" => $v->karyawan_id,
                                "cloud_id" => $v->cloud_id,
                                "company" => $v->company
                            ])->update([
                                "status_upload" => '1'
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function worker()
    {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 1,
            '--timeout' => 60,
        ]);
    }

    public function tes()
    {
        // Artisan::call('config:clear');
        // Artisan::call('cache:clear');
        // Artisan::call('view:clear');
        // Artisan::call('route:clear');
        // Artisan::call('config:cache');
        // Artisan::call('view:cache');
        // Artisan::call('route:cache');

        echo 'ok';
    }
}
