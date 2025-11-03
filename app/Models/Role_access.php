<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role_access extends Model
{
    use HasFactory;
    protected $table = 'role_access';
    protected $guarded = ['id'];

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function menu() {
        return $this->belongsTo(Menu::class);
    }
}
