<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'payment_type',
        'items',
        'status',
    ];

    protected $casts = [
        'items' => 'array',  // Automatically cast the items column to an array
    ];
}
