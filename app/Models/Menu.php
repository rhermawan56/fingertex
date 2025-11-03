<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function submenu() {
        return $this->hasMany(Sub_menu::class);
    }

    public function role_access() {
        return $this->hasMany(Role_access::class);
    }
}
