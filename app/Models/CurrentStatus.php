<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrentStatus extends Model
{
    use HasFactory;
    protected $table = 'current_status';

    protected $fillable = [
        'status',
    ];
}
