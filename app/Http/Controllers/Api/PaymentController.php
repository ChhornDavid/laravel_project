<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PayWayService;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $payWayService;

    public function __construct(PayWayService $payWayService)
    {
        $this->payWayService = $payWayService;
    }

    public function showCheckoutForm()
    {
        $item = [
            ['name' => 'test1', 'quantity' => '1', 'price' => '10.00'],
            ['name' => 'test2', 'quantity' => '1', 'price' => '10.00']
        ];

        $items = base64_encode(json_encode($item));
        $req_time = time();
        $transactionId = '24os-pr0001'; // or any unique transaction ID generation logic
        $amount = '100.00';
        $firstName = 'Sokha';
        $lastName = 'Tim';
        $phone = '093630466';
        $email = 'sokha.tim@ababank.com';
        $return_params = 'Hello World!';
        $type = 'purchase';
        $currency = 'USD';
        $shipping = '0.60';
        $merchant_id = config('payway.merchant_id');
        $payment_option = '';

        $hash = $this->payWayService->getHash(
            $req_time . $merchant_id . $transactionId . $amount . $items . $shipping .
            $firstName . $lastName . $email . $phone . $type . $payment_option .
            $currency . $return_params
        );

        return view('checkout', compact(
            'hash', 'transactionId', 'amount', 'firstName', 'lastName', 'phone', 'email',
            'items', 'return_params', 'shipping', 'currency', 'type', 'payment_option', 'merchant_id', 'req_time'
        ));
    }
}

