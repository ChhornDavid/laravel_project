<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function stripePost(Request $request)
    {
        try {
            // Set your Stripe secret key
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100,
                'currency' => 'usd',
                'payment_method' => $request->paymentMethodId,
                'description' => $request->description,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => 'https://your-website.com/success',
            ]);

            if ($paymentIntent->status === 'requires_action') {
                return response()->json([
                    'requires_action' => true,
                    'payment_intent_client_secret' => $paymentIntent->client_secret,
                ], 200);
            }

            if ($paymentIntent->status === 'succeeded') {
                return response()->json([
                    'status' => $paymentIntent->status,
                    'charge_id' => $paymentIntent->id,
                ], 201);
            }

            return response()->json([
                'error' => 'Payment failed. Status: ' . $paymentIntent->status,
            ], 400);
        } catch (\Exception $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 500);
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

            // Create a Stripe Checkout Session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('payment.success'), // Use named route
                'cancel_url' => route('payment.cancel'), // Use named route
                'client_reference_id' => 'order_id', // Unique identifier for the order
            ]);

            return response()->json([
                'payment_url' => $session->url,
                'payment_id' => $session->id,
            ]);
        } catch (\Exception $e) {
            Log::error("Error creating payment link", ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Error generating payment link'], 500);
        }
    }

    // Check Payment Status
    public function checkPaymentStatus($paymentId)
    {
        $stripeSecretKey = config('services.stripe.secret');
        Stripe::setApiKey($stripeSecretKey);

        try {
            $session = Session::retrieve($paymentId);

            if ($session->payment_status === 'paid') {
                return response()->json(['paymentStatus' => 'success']);
            } else {
                return response()->json(['paymentStatus' => 'pending']);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Unable to verify payment status'], 400);
        }
    }

    // Handle Webhook from Stripe
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;

                    if ($session->payment_status === 'paid') {
                        $orderId = $session->client_reference_id;
                    }
                    break;
                default:
                    break;
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 400);
        }
    }
}
