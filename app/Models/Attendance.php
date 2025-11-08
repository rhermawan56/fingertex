<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'absensi';
    protected $guarded = ['id'];
    public $timestamps = true;
    const CREATED_AT = null;
    const UPDATED_AT = null;
}
