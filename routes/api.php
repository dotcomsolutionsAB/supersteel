<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CreateController;
use App\Http\Controllers\ViewController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\DeleteController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceControllerPO;
use App\Http\Controllers\ImageDownloadController;
use App\Http\Middleware\GetUserRole;

Route::prefix('admin')->middleware(['auth:sanctum', GetUserRole::class . ':admin'])->group(function () {

    Route::post('/add_user', [CreateController::class, 'user']);
    Route::get('/view_user', [ViewController::class, 'user']);
    Route::post('/make_verify/{id}', [UpdateController::class, 'verify_user']);
    Route::patch('/make_unverify/{id}', [UpdateController::class, 'unverify_user']);
    Route::post('/update_user', [UpdateController::class, 'user']);
    Route::post('/update_password', [UpdateController::class, 'user_password']);
    Route::post('/logout', [CreateController::class, 'logout']);

    Route::get('/view_product', [ViewController::class, 'product']);
    Route::post('/public_get_product', [ViewController::class, 'publicProducts']);
    Route::post('/category', [ViewController::class, 'categories']);

    Route::post('/add_order', [CreateController::class, 'orders']);
    Route::post('/view_order', [ViewController::class, 'orders']);
    Route::post('/add_quotation', [CreateController::class, 'quotation']);
    Route::post('/add_purchase_order', [CreateController::class, 'purchase_order']);
    Route::post('/view_user_order/{id?}', [ViewController::class, 'orders_user_id']);
    Route::post('/add_order_items', [CreateController::class, 'orders_items']);
    Route::get('/view_order_items', [ViewController::class, 'order_items']);
    Route::get('/view_items_orders/{id}', [ViewController::class, 'orders_items_order_id']);
    Route::post('/update_order/{id?}', [UpdateController::class, 'order']);
    Route::post('/complete_order/{id?}', [UpdateController::class, 'complete_order']);
    Route::post('/cancel_order/{id?}', [UpdateController::class, 'cancel_order']);
    // Route::post('/make_partial', [UpdateController::class, 'partial_order']);
    // Route::post('/complete', [UpdateController::class, 'paid_order']);

    Route::post('/add_cart', [CreateController::class, 'cart']);
    Route::get('/view_cart', [ViewController::class, 'cart']);
    Route::get('/view_cart_user/{id?}', [ViewController::class, 'cart_user']);
    Route::patch('/update_cart/{id?}', [UpdateController::class, 'cart']);
    Route::delete('/delete_cart/{id}', [DeleteController::class, 'cart']);

    Route::post('/add_counter', [CreateController::class, 'counter']);
    Route::get('/view_counter', [ViewController::class, 'counter']);
    Route::get('/dashboard', [ViewController::class, 'dashboard_details']);
    
    Route::post('/generate_invoice/{orderId}', [InvoiceController::class, 'generateInvoice']);
    Route::post('/generate_invoice_po/{orderId}', [InvoiceControllerPO::class, 'generateorderInvoicePO']);
    Route::get('/return_order/{orderId}', [ViewController::class, 'return_order']);
    Route::post('/add_invoice', [CreateController::class, 'make_invoice']);

    Route::post('/spare_product/{code?}', [ViewController::class, 'get_spares']);
    Route::post('/spares_pricelist/{code}', [InvoiceController::class, 'price_spares']);
    Route::post('/pricelist', [InvoiceController::class, 'price_list']);
});

Route::prefix('manager')->middleware(['auth:sanctum', GetUserRole::class . ':manager'])->group(function () {

    Route::get('/view_user', [ViewController::class, 'user']);
    Route::post('/update_user', [UpdateController::class, 'user']);
    Route::post('/update_password', [UpdateController::class, 'user_password']);
    Route::post('/logout', [CreateController::class, 'logout']);

    Route::get('/view_product', [ViewController::class, 'product']);
    Route::post('/get_product', [ViewController::class, 'get_product']);
    Route::post('/category', [ViewController::class, 'categories']);
    Route::post('/sub_category', [ViewController::class, 'sub_category']);

    Route::post('/add_order', [CreateController::class, 'orders']);
    Route::post('/add_quotation', [CreateController::class, 'quotation']);
    Route::post('/view_user_order/{id?}', [ViewController::class, 'orders_user_id']);
    Route::post('/update_order/{id?}', [UpdateController::class, 'order']);
    Route::post('/complete_order/{id?}', [UpdateController::class, 'complete_order']);
    Route::post('/cancel_order/{id?}', [UpdateController::class, 'cancel_order']);

    Route::post('/add_order_items', [CreateController::class, 'orders_items']);
    Route::get('/view_order_items', [ViewController::class, 'order_items']);
    Route::get('/view_items_orders/{id}', [ViewController::class, 'orders_items_order_id']);

    Route::post('/add_cart', [CreateController::class, 'cart']);
    Route::get('/view_cart', [ViewController::class, 'cart']);
    Route::get('/view_cart_user/{id?}', [ViewController::class, 'cart_user']);
    Route::patch('/update_cart/{id?}', [UpdateController::class, 'cart']);
    Route::delete('/delete_cart/{id}', [DeleteController::class, 'cart']);

    Route::get('/dashboard', [ViewController::class, 'dashboard_details']);

    Route::post('/generate_invoice/{orderId}', [InvoiceController::class, 'generateInvoice']);
    Route::get('/return_order/{orderId}', [ViewController::class, 'return_order']);
    Route::post('/add_invoice', [CreateController::class, 'make_invoice']);

    Route::post('/spare_product/{code?}', [ViewController::class, 'get_spares']);
    Route::post('/spares_pricelist/{code}', [InvoiceController::class, 'price_spares']);
    Route::post('/pricelist', [InvoiceController::class, 'price_list']);
});

Route::prefix('user')->middleware(['auth:sanctum', GetUserRole::class . ':user'])->group(function () {

    Route::get('/get_details', [ViewController::class, 'user_details']);
    Route::post('/update_user', [UpdateController::class, 'user']);
    Route::post('/update_password', [UpdateController::class, 'user_password']);
    Route::get('/logout', [CreateController::class, 'logout']);

    Route::get('/view_product', [ViewController::class, 'product']);
    Route::post('/get_product', [ViewController::class, 'get_product']);
    Route::post('/category', [ViewController::class, 'categories']);
    Route::post('/sub_category', [ViewController::class, 'sub_category']);

    Route::post('/add_order', [CreateController::class, 'orders']);
    Route::post('/view_user_order/{id?}', [ViewController::class, 'orders_user_id']);

    Route::post('/add_cart', [CreateController::class, 'cart']);
    Route::get('/view_cart_user/{id?}', [ViewController::class, 'cart_user']);
    Route::patch('/update_cart/{id}', [UpdateController::class, 'cart']);
    Route::delete('/delete_cart/{id}', [DeleteController::class, 'cart']);

    Route::post('/spare_product/{code?}', [ViewController::class, 'get_spares']);
    Route::post('/spares_pricelist/{code}', [InvoiceController::class, 'price_spares']);
    Route::post('/pricelist', [InvoiceController::class, 'price_list']);
    Route::delete('/delete_user/{id}', [DeleteController::class, 'user']);
});

Route::post('/get_product', [ViewController::class, 'get_product']);
Route::post('/public_category', [ViewController::class, 'publicCategories']);

Route::get('/fetch_price_type', [ViewController::class, 'getUniquePriceTypes']);

Route::get('/delete-account', [DeleteController::class, 'showDeleteAccountForm'])->name('delete.account.form');
Route::post('/delete-account', [UserController::class, 'deleteAccount'])->name('delete.account');

Route::post('/login/{otp?}', [CreateController::class, 'login']);
Route::post('/register_user', [CreateController::class, 'user']);
Route::post('/get_otp', [UpdateController::class, 'generate_otp']);

Route::get('/fetch_products', [CsvImportController::class, 'importProduct']);
Route::get('/fetch_users', [CsvImportController::class, 'importUser']);
Route::get('/fetch_category', [CsvImportController::class, 'importCategory']);
Route::get('/fetch_sub_category', [CsvImportController::class, 'importSubCategory']);

Route::get('/sync_images', [ImageDownloadController::class, 'fetchAndSaveImages']);

Route::post('/view_product_guest', [ViewController::class, 'get_product_guest']);
