<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeMachine extends Model
{
    use HasFactory;
    protected $table = 'employee_machines';
    protected $primaryKey = 'em_id';
    protected $guarded = ['em_id'];
    const CREATED_AT = 'em_creation';
    const UPDATED_AT = null;

    public function employee() {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function machine() {
        return $this->belongsTo(Machine::class, 'msn_id', 'msn_id');
    }
}
