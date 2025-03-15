<!-- resources/views/checkout.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <title>PayWay Checkout</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="author" content="PayWay">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>

    <!-- Custom Styles -->
    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #f0f0f0, #d9e2ef);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Centered Container */
        .container {
            text-align: center;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            max-width: 90%;
        }

        /* Title */
        .container h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            font-weight: 600;
        }

        /* Checkout Button */
        #checkout_button {
            background: #007bff;
            color: white;
            border: none;
            padding: 14px 28px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
            width: 100%;
            max-width: 250px;
            margin-top: 10px;
        }

        /* Button Hover Effect */
        #checkout_button:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 400px) {
            .container {
                width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>TOTAL: ${{ $amount }}</h2>
        <form method="POST" target="aba_webservice" action="{{ config('payway.api_url') }}" id="aba_merchant_request">
            @csrf
            <input type="hidden" name="hash" value="{{ $hash }}" id="hash" />
            <input type="hidden" name="tran_id" value="{{ $transactionId }}" id="tran_id" />
            <input type="hidden" name="amount" value="{{ $amount }}" id="amount" />
            <input type="hidden" name="firstname" value="{{ $firstName }}" />
            <input type="hidden" name="lastname" value="{{ $lastName }}" />
            <input type="hidden" name="phone" value="{{ $phone }}" />
            <input type="hidden" name="email" value="{{ $email }}" />
            <input type="hidden" name="items" value="{{ $items }}" id="items" />
            <input type="hidden" name="return_params" value="{{ $return_params }}" />
            <input type="hidden" name="shipping" value="{{ $shipping }}" />
            <input type="hidden" name="currency" value="{{ $currency }}" />
            <input type="hidden" name="type" value="{{ $type }}" />
            <input type="hidden" name="payment_option" value="{{ $payment_option }}" />
            <input type="hidden" name="merchant_id" value="{{ $merchant_id }}" />
            <input type="hidden" name="req_time" value="{{ $req_time }}" />
        </form>
        <input type="button" id="checkout_button" value="Checkout Now">
    </div>

    <!-- PayWay Plugin -->
    <script src="https://checkout.payway.com.kh/plugins/checkout2-0.js"></script>

    <!-- Checkout Script -->
    <script>
        $(document).ready(function () {
            $('#checkout_button').click(function () {
                AbaPayway.checkout();
            });
        });
    </script>
</body>
</html>
