<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function stripePost(Request $request)
    {
        try {
            $stripeSecretKey = config('services.stripe.secret');

            $stripe = new \Stripe\StripeClient($stripeSecretKey);

            $testToken = 'tok_visa';
            $response = $stripe->charges->create([
                'amount' => $request->amount * 100,
                'currency' => 'usd',
                'source' => $testToken,
                'description' => $request->description,
            ]);

            return response()->json([
                'status' => $response->status,
                'charge_id' => $response->id,
            ], 201);
        } catch (Exception $ex) {
            return response()->json([
                'error' => $ex->getMessage()
            ], 500);
        }
    }

    public function createPaymentLink(Request $request)
    {
        $stripeSecretKey = config('services.stripe.secret');
        Stripe::setApiKey($stripeSecretKey);

        try {
            $validatedData = $request->validate([
                'items' => 'required|array',
                'items.*.product_name' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric',
                'currency' => 'required|string',
            ]);

            Log::info('Validated Data:', ['validatedData' => $validatedData]);


            $lineItems = [];
            foreach ($validatedData['items'] as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => $validatedData['currency'],
                        'product_data' => [
                            'name' => $item['product_name'],
                        ],
                        'unit_amount' => (int) ($item['price'] * 100)
                    ],
                    'quantity' => $item['quantity']
                ];
            }
            Log::info('Line Items:', ['lineItems' => $lineItems]);
            // Create a Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('payment.success'), // Use named route
                'cancel_url' => route('payment.cancel'), // Use named route
            ]);

            return response()->json([
                'payment_url' => $session->url,
            ]);
        } catch (\Exception $e) {
            Log::error("Error creating payment link", ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Error generating payment link'], 500);
        }
    }
    public function success()
    {
        return response()->json(['message' => 'Payment success'], 200);
    }

    public function cancel()
    {
        return response()->json(['message' => 'Payment failed'], 200);
    }
}
