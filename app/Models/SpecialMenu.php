<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialMenu extends Model
{
    use HasFactory;

    protected $fillable = ['name'];  
    protected $table = 'special_menu';
}
