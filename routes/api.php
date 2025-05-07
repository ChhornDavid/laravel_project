<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CurrentStatusController;
use App\Http\Controllers\Api\KitchenOrderController;
use App\Http\Controllers\Api\OrderCashController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SpecialMenuController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// Protected Routes
Route::middleware('jwt.auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    //User
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/showusers/{user}', [UserController::class, 'show']);
    Route::post('/addusers', [UserController::class, 'store']);
    Route::put('/updateusers/{user}', [UserController::class, 'update']);
    Route::delete('/deleteusers/{user}', [UserController::class, 'destroy']);

    //Category
    Route::put('/updatecategories/{category}', [CategoryController::class, 'update']);
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('addcategories', [CategoryController::class, 'store']);
    Route::get('showcategories/{category}', [CategoryController::class, 'show']);
    Route::delete('deletecategory/{category}', [CategoryController::class, 'destroy']);

    //Special menu
    Route::get('/special-menus', [SpecialMenuController::class, 'index']);
    Route::post('/addspecial-menus', [SpecialMenuController::class, 'store']);
    Route::get('/showspecial-menus/{special_menu}', [SpecialMenuController::class, 'show']);
    Route::put('/updatespecial-menus/{special_menu}', [SpecialMenuController::class, 'update']);
    Route::delete('/deletespecial-menus/{special_menu}', [SpecialMenuController::class, 'destroy']);

    //product
    Route::get('/products', [ProductController::class, 'index']);         // Fetch all products
    Route::post('/addproducts', [ProductController::class, 'store']);        // Create a product
    Route::get('showproductds/{products}', [ProductController::class, 'show']);      // Get a single product
    Route::put('updateproducts/{products}', [ProductController::class, 'update']);    // Update a product
    Route::delete('deleteproduct/{products}', [ProductController::class, 'destroy']); // Delete a product

    //Order
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    Route::get('/getitem', [OrderController::class, 'LastOrder']);
    

    //Payment by credit card
    Route::post('/stripe', [StripeController::class, 'stripePost']);

    //Payment by Cash
    Route::post('/pending-orders', [OrderCashController::class, 'store']);
    Route::get('/admin/pending-orders', [OrderCashController::class, 'listPendingOrders']);
    Route::post('/admin/approve/{id}', [OrderCashController::class, 'approveOrder']);
    Route::post('/admin/decline/{id}', [OrderCashController::class, 'declineOrder']);

    //Payment by Scan
    Route::post('/payment/create', [StripeController::class, 'createPaymentLink']);
    Route::get('/payment/status/{paymentId}', [StripeController::class, 'checkPaymentStatus']);
    Route::post('/payment/webhook', [StripeController::class, 'handleWebhook']);
    Route::get('/payment/success', [StripeController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [StripeController::class, 'cancel'])->name('payment.cancel');

    //Kitchen order
    Route::get('/kitchen/orders', [KitchenOrderController::class, 'index']);
    Route::post('/kitchen/orders', [KitchenOrderController::class, 'store']);
    Route::put('/kitchen/orders/{id}/status', [KitchenOrderController::class, 'updateStatus']);

    //ABA Payment
    Route::post('/payment/checkout', [PaymentController::class, 'checkout']);

    

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

//Currentstatus
// Route::get('/status',[CurrentStatusController::class, 'index']);
// Route::post('/status', [CurrentStatusController::class, 'store']);

// User
Route::get('/users', [UserController::class, 'index']);
Route::post('/refresh', [AuthController::class, 'refresh']);

