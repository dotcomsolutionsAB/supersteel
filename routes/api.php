<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CreateController;

use App\Http\Controllers\ViewController;

use App\Http\Controllers\UpdateController;

use App\Http\Controllers\DeleteController;

use App\Http\Controllers\CsvImportController;

use App\Http\Controllers\InvoiceController;

use App\Http\Middleware\GetUserRole;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('admin')->middleware(['auth:sanctum', GetUserRole::class . ':admin'])->group(function () {

    Route::post('/add_user', [CreateController::class, 'user']);

    Route::get('/view_user', [ViewController::class, 'user']);

    Route::patch('/make_verify/{id}', [UpdateController::class, 'verify_user']);

    Route::post('/update_user', [UpdateController::class, 'user']);

    Route::post('/update_password', [UpdateController::class, 'user_password']);

    // Route::get('/logout', [CreateController::class, 'webLogout']);
    Route::post('/logout', [CreateController::class, 'logout']);

    // Route::post('/add_product', [CreateController::class, 'product']);

    Route::get('/view_product', [ViewController::class, 'product']);

    // Route::get('/lng_product/{lang?}', [ViewController::class, 'lng_product']);

    Route::post('/get_product', [ViewController::class, 'get_product']);

    // Route::get('/category', [ViewController::class, 'categories']);

    // Route::post('/lng_get_product/{lang?}', [ViewController::class, 'lng_get_product']);

    // Route::get('/category/{lang?}', [ViewController::class, 'lng_categories']);

    // Route::get('/lng_category', [ViewController::class, 'categories']);

    Route::post('/add_order', [CreateController::class, 'orders']);

    Route::get('/view_order', [ViewController::class, 'orders']);
    
    Route::get('/view_user_order/{id?}', [ViewController::class, 'orders_user_id']);
    
    Route::post('/add_order_items', [CreateController::class, 'orders_items']);
    
    Route::get('/view_order_items', [ViewController::class, 'order_items']);
    
    Route::get('/view_items_orders/{id}', [ViewController::class, 'orders_items_order_id']);

    Route::post('/add_cart', [CreateController::class, 'cart']);

    Route::get('/view_cart', [ViewController::class, 'cart']);

    Route::get('/view_cart_user/{id?}', [ViewController::class, 'cart_user']);
    
    Route::patch('/update_cart/{id?}', [UpdateController::class, 'cart']);

    Route::delete('/delete_cart/{id}', [DeleteController::class, 'cart']);

    Route::get('/fetch_products', [CsvImportController::class, 'importProduct']);

    Route::get('/fetch_users', [CsvImportController::class, 'importUser']);

    Route::get('/fetch_category', [CsvImportController::class, 'importCategory']);

    Route::post('/add_counter', [CreateController::class, 'counter']);

    Route::get('/view_counter', [ViewController::class, 'counter']);

    Route::get('/dashboard', [ViewController::class, 'dashboard_details']);

    Route::post('/generate_invoice/{orderId}', [InvoiceController::class, 'generateInvoice']);

    Route::get('/return_order/{orderId}', [ViewController::class, 'return_order']);

    Route::post('/add_invoice', [CreateController::class, 'make_invoice']);

    Route::get('/spare_product/{code?}', [ViewController::class, 'get_spares']);
});

Route::prefix('user')->middleware(['auth:sanctum', GetUserRole::class . ':user'])->group(function () {

    Route::get('/get_details', [ViewController::class, 'user_details']);

    Route::post('/update_user', [UpdateController::class, 'user']);

    Route::post('/update_password', [UpdateController::class, 'user_password']);

    Route::get('/logout', [CreateController::class, 'logout']);

    // Route::get('/cart_user', [ViewController::class, 'cart_user']);
    Route::post('/add_cart', [CreateController::class, 'cart']);

    Route::get('/view_cart_user/{id?}', [ViewController::class, 'cart_user']);

    Route::patch('/update_cart/{id}', [UpdateController::class, 'cart']);

    Route::delete('/delete_cart/{id}', [DeleteController::class, 'cart']);

    Route::post('/add_order', [CreateController::class, 'orders']);

    // Route::get('/generate_invoice/{userId}/{orderId}', [InvoiceController::class, 'generateInvoice']);
    // Route::get('/generate_invoice/{orderId}', [InvoiceController::class, 'generateInvoice']);

});

Route::prefix('manager')->middleware(['auth:sanctum', GetUserRole::class . ':manager'])->group(function () {
    Route::get('/view_user', [ViewController::class, 'user']);
});
Route::post('/login/{otp?}', [CreateController::class, 'login']);

Route::post('/register_user', [CreateController::class, 'user']);

// Route::get('/view_user', [ViewController::class, 'user']);

Route::post('/get_otp', [UpdateController::class, 'generate_otp']);