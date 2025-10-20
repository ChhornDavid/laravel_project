<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreOrders extends Model
{
    use HasFactory;

    protected $table = 'store_orders';
    protected $fillable = [
        'user_id',
        'items',
        'order_number',
        'status',
        'order_paid'
    ];

    protected $casts = [
        'items' => 'array',
        'status' => 'boolean',
        'order_paid' => 'boolean',
    ];
}
