<?php

namespace App\Http\Controllers;

use App\Jobs\HitApiJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Dashabsensi;
use App\Models\Employee;
use App\Models\EmployeeMachine;
use App\Models\Machine;
use App\Services\GlobalServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

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
        // $log = (object) $log->getOriginalContent();

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
            if ($data->cloud_id != 'E666C4D19B4AB630' && $data->cloud_id != 'E666C4D19B491330') {
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

            return response()->json([
                'status' => true,
                'messages' => 'ok'
            ], 200);
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

        return response()->json($log, 200);
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

        // if ($data->cloud_id == 'C2622D141F372937') {
        //     EmployeeMachine::where(['cloud_id' => $data->cloud_id])->delete();

        //     foreach ($pin as $k => $v) {
        //         $this->global->deleteuserinfo($k + 1, $v, $machine);
        //     }
        // }

        $employee = Employee::whereIn('kar_id', $pin)->get();
        $employee = collect($employee)->pluck('kar_id')->toArray();

        $inEmployee = collect($pin)->filter(function ($item) use ($employee) {
            return in_array($item, $employee);
        })->values()->toArray();

        $notEmployee = collect(array_diff($pin, $employee))->values()->toArray();

        $this->checkConnection();
        $this->login($this->ip);

        // in employee process
        if ($inEmployee) {
            // kahaptex process
            $inEmployeekahap = collect($inEmployee)->filter(function ($item) {
                return substr($item, 0, 2) != "80";
            })->values()->toArray();

            $machineemployeeall = EmployeeMachine::where([
                'cloud_id' => $data->cloud_id
            ])
                ->whereIn('kar_id', $inEmployeekahap)
                ->get();

            $karInMachine = collect($machineemployeeall)->pluck('kar_id')->values()->toArray();
            $arrayDiff = collect(array_diff($inEmployeekahap, $karInMachine))->values()->toArray();

            if ($arrayDiff) {
                foreach ($arrayDiff as $k => $v) {
                    DB::statement(
                        "INSERT INTO employee_machines
                        (employee_id, kar_id, msn_id, cloud_id, em_creation)
                        SELECT employee_id, kar_id, ?, ?, NOW()
                        FROM employees
                        WHERE kar_id = ?
                        ",
                        [$machine->msn_id, $machine->cloud_id, $v]
                    );
                }
            }

            // sinar terang process
            $inEmployeesinter = collect($inEmployee)->filter(function ($item) {
                return substr($item, 0, 2) == "80";
            })->values()->toArray();

            $machineemployeeall = EmployeeMachine::where([
                'cloud_id' => $data->cloud_id
            ])
                ->whereIn('kar_id', $inEmployeesinter)
                ->get();

            $karInMachine = collect($machineemployeeall)->pluck('kar_id')->values()->toArray();
            $arrayDiff = collect(array_diff($inEmployeesinter, $karInMachine))->values()->toArray();

            if ($arrayDiff) {
                foreach ($arrayDiff as $k => $v) {
                    DB::statement(
                        "INSERT INTO employee_machines
                        (employee_id, kar_id, msn_id, cloud_id, em_creation)
                        SELECT employee_id, kar_id, ?, ?, NOW()
                        FROM employees
                        WHERE kar_id = ?
                        ",
                        [$machine->msn_id, $machine->cloud_id, $v]
                    );
                }
            }
        }

        // not employee process
        // kahaptex process
        $notEmployeekahap = collect($notEmployee)->filter(function ($item) {
            return substr($item, 0, 2) != 80;
        })->values()->toArray();

        $employeelocalkahap = $this->global->allemployeelocal($this->ip, $notEmployeekahap, 'PT KAHAPTEX', $this->JWT_KEY);
        $employeelocalkahap = $employeelocalkahap->json();

        $employeelocalkahap = (object) $employeelocalkahap;
        $arrayemployeelocalkahap = collect($employeelocalkahap->data)->pluck('kar_id')->unique()->values()->toArray();
        $diffemployeekahap = collect(array_diff($notEmployeekahap, $arrayemployeelocalkahap))->values()->toArray();

        // hapus data di mesin
        if ($diffemployeekahap && $notEmployeekahap) {
            foreach ($diffemployeekahap as $k => $v) {
                // $this->global->deleteuserinfo($k + 1, $v, $machine);
            }
        }

        if ($employeelocalkahap->data && $notEmployeekahap) {
            // Update data ke fingertex
            foreach ($arrayemployeelocalkahap as $k => $v) {
                $this->global->userinfo($k + 1, $v, $machine);
            }
        }

        // sinar terang process
        $notEmployeesinter = collect($notEmployee)->filter(function ($item) {
            return substr($item, 0, 2) == 80;
        })->values()->toArray();
        $notEmployeesinterMap = collect($notEmployeesinter)->map(function ($item) {
            $item = substr($item, 0, 2) == '80' ? substr($item, 2) : $item;
            return $item;
        })->values()->toArray();

        $employeelocalsinter = $this->global->allemployeelocal($this->ip, $notEmployeesinterMap, 'PT SINAR TERANG', $this->JWT_KEY);
        $employeelocalsinter = $employeelocalsinter->json();

        $employeelocalsinter = (object) $employeelocalsinter;
        $arrayemployeelocalsinter = collect($employeelocalsinter->data)->pluck('kar_id')->unique()->values()->toArray();
        $arrayemployeelocalsintermap = collect($arrayemployeelocalsinter)->map(function ($item) {
            $item = substr($item, 0, 2) == '80' ? substr($item, 2) : $item;
            return $item;
        })->values()->toArray();

        $diffemployeesinter = collect(array_diff($notEmployeesinterMap, $arrayemployeelocalsintermap))->values()->toArray();
        // dd($diffemployeesinter);

        // hapus data di mesin
        if ($diffemployeesinter && $notEmployeesinter) {
            // foreach ($diffemployeesinter as $k => $v) {
            //     $this->global->deleteuserinfo($k + 1, $v, $machine);
            // }
        }

        if ($employeelocalsinter->data && $notEmployeesinter) {
            $arrayemployeelocalsintermap = collect($arrayemployeelocalsinter)->map(function ($item) {
                $item = substr($item, 0, 2) == '80' ? $item : "80{$item}";
                return $item;
            })->values()->toArray();

            foreach ($arrayemployeelocalsintermap as $k => $v) {
                $this->global->userinfo($k + 1, $v, $machine);
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

            if (!$employee->employee_name_machine) {
                Employee::where([
                    'kar_id' => $data->data->pin
                ])->update([
                    "employee_name_machine" => $data->data->name
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

    public function registermachine($cloudid)
    {
        $date = date('Y_m_d');

        $filename = "get_userid_list_{$date}.txt";

        try {
            $data = [];
            $content = Storage::get($filename);
            $lines = explode("\n", $content);

            foreach ($lines as $line) {
                $l = json_decode($line, true);
                unset($l['type']);
                $data[] = $l;
            }

            $data = collect($data)->filter(function ($item) {
                return $item;
            })->values()->toArray();

            $data = collect($data)
                ->filter(function ($item) use ($cloudid) {
                    return $item['cloud_id'] == $cloudid;
                })
                ->values()
                ->toArray();

            $dataTotal = collect($data)->groupBy(function ($item) {
                return $item['data']['total'];
            });

            $checkdata = $dataTotal->filter(function ($item) {
                return $item->count() > 1;
            })
                ->keys()
                ->values()
                ->toArray();

            if (!$checkdata) {
                $this->global->fnallpin('100', $cloudid);
            }

            return response()->json([
                'status' => true,
                'messages' => true,
                'data' => $data
            ], 200);
        } catch (\Exception $e) {

            $this->global->fnallpin('100', $cloudid);

            return response()->json([
                'status' => false,
                'messages' => $e->getMessage(),
                'data' => []
            ], 500);
        }
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

        // dd($data->data);

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

        echo 'ok';
    }

    public function cronlocal($day)
    {
        $company = 'PT KAHAPTEX';
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

        $machineall = Machine::where([
            'msn_status' => '1',
            'company' => $company
        ])->get();
        if (!$machineall) {
            abort(404);
        }
        $machineCompany = collect($machineall)->pluck('cloud_id')->unique()->values()->toArray();

        $attendance = Dashabsensi::where([
            'tgl_absen' => $dateRequest,
            'status_upload' => '0',
        ])
            ->whereIn('cloud_id', $machineCompany)
            ->limit(100)->get();
        if (!$attendance) {
            abort(404);
        }

        $pin = collect($attendance)->pluck('karyawan_id')->unique()->values()->toArray();
        $pin = collect($pin)->map(function ($item) {
            $item = substr($item, 0, 2) == 80 ? substr($item, 2) : $item;
            return $item;
        });

        $employeelocal = $this->global->allemployeelocal($this->ip, $pin, $company, $this->JWT_KEY);
        $employeelocal = $employeelocal->json();
        if (!$employeelocal['data']) {
            abort(404);
        }
        $employeelocal = (object) $employeelocal;

        $group = collect($employeelocal->data)->pluck('group')->unique()->values()->toArray();
        $shift = $this->global->employeeshift($this->ip, $group, $company, $dateRequest, $this->JWT_KEY);
        $shift = $shift->json();
        if (!$shift['data']) {
            abort(404);
        }
        $shift = (object) $shift;

        foreach ($attendance as $k => &$v) {
            $employeelocalFilter = collect($employeelocal->data)->filter(function ($item) use ($v) {
                $karId = $item['kar_id'];
                if (stripos($v->company, 'SINAR TERANG') !== false || stripos($v->company, 'SINARTERANG') !== false) {
                    $karId = "80{$item['kar_id']}";
                }
                return $karId == $v->karyawan_id;
            })->values()->toArray();

            $machine = collect($machineall)->filter(function ($item) use ($v) {
                return $item->cloud_id == $v->cloud_id;
            })->values()->toArray();

            if ($employeelocalFilter && $machine) {
                $machine = (object) $machine[0];
                $employeelocalFilter = (object) $employeelocalFilter[0];
                $v->group = $employeelocalFilter->group;

                $tglShift = $v->tgl_absen;
                if (substr($v->jam, 0, 2) > -1 && substr($v->jam, 0, 2) <= 7 && stripos($v->status, 'pulang') > -1) {
                    $tglShift = Carbon::parse($v->tgl_absen)->subDay()->toDateString();
                }
                $v->tgl_shift = $tglShift;

                $shiftFilter = collect($shift->data)->filter(function ($item) use ($v) {
                    return $item['drtgl'] <= $v->tgl_shift && $item['ketgl'] >= $v->tgl_shift && $item['id_group'] == $v->group;
                })->values()->toArray();

                if ($shiftFilter) {
                    $shiftFilter = (object) $shiftFilter[0];

                    if ($shiftFilter->id_shift == 'Shift 1' && stripos($v->status, 'pulang') > -1) {
                        $v->tgl_shift = $v->tgl_absen;
                    }

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

                            $msnIdInclude = [14, 15];

                            if (in_array($machine->msn_id, $msnIdInclude)) {
                                $employeemachine = EmployeeMachine::where([
                                    'kar_id' => $v->karyawan_id,
                                    'msn_id' => $machine->msn_id
                                ])->first();

                                $em = Employee::where(['kar_id' => $v->karyawan_id])->first();

                                if (!$employeemachine) {
                                    EmployeeMachine::create([
                                        "employee_id" => $em->em_id,
                                        "kar_id" => $v->karyawan_id,
                                        "msn_id" => $machine->msn_id,
                                        "cloud_id" => $machine->cloud_id,
                                        "em_creation" => date('Y-m-d')
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }

        echo 'ok';
    }

    public function cronlocal2($day)
    {
        $company = 'PT SINAR TERANG';
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

        $machineall = Machine::where([
            'msn_status' => '1',
            'company' => $company
        ])->get();
        if (!$machineall) {
            abort(404);
        }
        $machineCompany = collect($machineall)->pluck('cloud_id')->unique()->values()->toArray();

        $attendance = Dashabsensi::where([
            'tgl_absen' => $dateRequest,
            'status_upload' => '0',
        ])
            ->whereIn('cloud_id', $machineCompany)
            ->limit(100)->get();
        if (!$attendance) {
            abort(404);
        }

        $pin = collect($attendance)->pluck('karyawan_id')->unique()->values()->toArray();
        $pin = collect($pin)->map(function ($item) {
            $item = substr($item, 0, 2) == 80 ? substr($item, 2) : $item;
            return $item;
        });

        $employeelocal = $this->global->allemployeelocal($this->ip, $pin, $company, $this->JWT_KEY);
        $employeelocal = $employeelocal->json();
        if (!$employeelocal['data']) {
            abort(404);
        }
        $employeelocal = (object) $employeelocal;

        $group = collect($employeelocal->data)->pluck('group')->unique()->values()->toArray();
        $shift = $this->global->employeeshift($this->ip, $group, $company, $dateRequest, $this->JWT_KEY);
        $shift = $shift->json();
        if (!$shift['data']) {
            abort(404);
        }
        $shift = (object) $shift;

        foreach ($attendance as $k => &$v) {
            $employeelocalFilter = collect($employeelocal->data)->filter(function ($item) use ($v) {
                $karId = $item['kar_id'];
                if (stripos($v->company, 'SINAR TERANG') !== false || stripos($v->company, 'SINARTERANG') !== false) {
                    $karId = "80{$item['kar_id']}";
                }
                return $karId == $v->karyawan_id;
            })->values()->toArray();

            $machine = collect($machineall)->filter(function ($item) use ($v) {
                return $item->cloud_id == $v->cloud_id;
            })->values()->toArray();

            if ($employeelocalFilter && $machine) {
                $machine = (object) $machine[0];
                $employeelocalFilter = (object) $employeelocalFilter[0];
                $v->group = $employeelocalFilter->group;

                $tglShift = $v->tgl_absen;
                if (substr($v->jam, 0, 2) > -1 && substr($v->jam, 0, 2) <= 7 && stripos($v->status, 'pulang') > -1) {
                    $tglShift = Carbon::parse($v->tgl_absen)->subDay()->toDateString();
                }
                $v->tgl_shift = $tglShift;

                $shiftFilter = collect($shift->data)->filter(function ($item) use ($v) {
                    return $item['drtgl'] <= $v->tgl_shift && $item['ketgl'] >= $v->tgl_shift && $item['id_group'] == $v->group;
                })->values()->toArray();

                if ($shiftFilter) {
                    $shiftFilter = (object) $shiftFilter[0];

                    if ($shiftFilter->id_shift == 'Shift 1' && stripos($v->status, 'pulang') > -1) {
                        $v->tgl_shift = $v->tgl_absen;
                    }

                    $localattlog = $this->global->localattlog($this->ip, $machine, $this->JWT_KEY, $v);
                    $localattlog = $localattlog->json();

                    if ($localattlog) {
                        $localattlog = (object) $localattlog;

                        if (!$localattlog->data) {
                            $save = collect($localattlog)->toArray();
                            $save['karyawan_id'] = $v->karyawan_id;
                            $save['karyawan'] = $v->karyawan_name;
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

        echo 'ok';
    }

    public function queue($key, $day)
    {
        if ($key == '1') {
            HitApiJob::dispatch(url("api/cronlocal/{$day}"));
        }

        if ($key == '2') {
            HitApiJob::dispatch(url("api/cronlocal2/{$day}"));
        }
    }

    public function worker()
    {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--tries' => 1,
            '--timeout' => 60,
            // '--max-time' => 180
        ]);
    }

    public function tes()
    {
        // Artisan::call('config:clear');
        // Artisan::call('cache:clear');
        // Artisan::call('view:clear');
        // Artisan::call('route:clear');

        // sleep(5);

        // Artisan::call('config:cache');
        // sleep(1);
        // Artisan::call('view:cache');
        // sleep(1);
        // Artisan::call('route:cache');
        // sleep(1);
        // Artisan::call('optimize');

        // sleep(1);
        Artisan::call('queue:work --stop-when-empty --tries=1');

        echo 'ok';
    }
}
