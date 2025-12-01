<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RevenueExport implements FromArray, WithHeadings
{
    protected $days;

    public function __construct($days)
    {
        $this->days = $days;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Amount',
        ];
    }

    public function array(): array
    {
        $startDate = Carbon::now()->subDays($this->days);

        $orders = Order::where('created_at', '>=', $startDate)
                        ->orderBy('created_at', 'asc')
                        ->get();

        return $orders->map(function ($order) {
            return [
                $order->created_at->format('Y-m-d'),
                $order->amount
            ];
        })->toArray();
    }
}
