<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductModel;
use App\Models\User;
use App\Models\OrderModel;
use App\Models\OrderItemsModel;
use App\Models\CartModel;
use App\Models\CounterModel;
use App\Models\InvoiceModel;
use App\Models\InvoiceItemsModel;
use Illuminate\Support\Facades\Auth;
use Hash;
use Carbon\Carbon;
use App\Http\Controllers\InvoiceController;
use App\Utils\sendWhatsAppUtility;

class CreateController extends Controller
{
    public function user(Request $request)
    {
        $request->validate([
            'email' => 'required|unique:users,email',
            'mobile' => ['required', 'string', 'size:13', 'unique:users'],
            'name' => 'required',
            'password' => 'required',
            'role' => 'required',
            'address_line_1' => 'required',
            'city' => 'required',
            'pincode' => 'required',
        ]);
        
            $create_user = User::create([
                'name' => $request->input('name'),
                'password' => bcrypt($request->input('password')),
                'email' => strtolower($request->input('email')),
                'mobile' => $request->input('mobile'),
                'role' => $request->input('role'),
                'address_line_1' => $request->input('address_line_1'),
                'address_line_2' => $request->input('address_line_2'),
                'city' => $request->input('city'),
                'pincode' => $request->input('pincode'),
                'gstin' => $request->input('gstin'),
                'state' => $request->input('state'),
                'country' => $request->input('country'),
            ]);

        unset($create_user['id'], $create_user['created_at'], $create_user['updated_at']);


        if (isset($create_user)) {


            $templateParams = [
                'name' => 'ace_new_user_registered', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $create_user->name,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_user->mobile,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_user->state,
                            ],
                        ],
                    ]
                ],
            ];
            
            $whatsAppUtility = new sendWhatsAppUtility();
            
            $response = $whatsAppUtility->sendWhatsApp('+918961043773', $templateParams, '', 'User Register');
  
            return response()->json([
                'message' => 'User created successfully!',
                'data' => $create_user
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed to create record'
            ], 400);
        }    
    }

    public function login(Request $request, $otp = null)
    {
        if($otp)
        {
            $request->validate([
                'mobile' => ['required', 'string', 'size:13'],
            ]);

            $otpRecord = User::select('otp', 'expires_at')
            ->where('mobile', $request->mobile)
            ->first();
            
            if ($otpRecord) 
            {
                // Validate OTP and expiry
                if (!$otpRecord || $otpRecord->otp != $otp) {
                    return response()->json(['message' => 'Invalid OTP.'], 400);
                }

                if ($otpRecord->expires_at < now()) {
                    return response()->json(['message' => 'OTP has expired.'], 400);
                } 

                else 
                {
                    // Remove OTP record after successful validation
                    User::select('otp')->where('mobile', $request->mobile)->update(['otp' => null, 'expires_at' => null]);

                    // Retrieve the user
                    $user = User::with('manager:id,mobile')
                                ->where('mobile', $request->mobile)->first();

                    // Generate a Sanctum token
                    $token = $user->createToken('API TOKEN')->plainTextToken;
        
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'token' => $token,
                            'name' => $user->name,
                            'role' => $user->role,
                            'manager_mobile_number' => $user->manager ? $user->manager->mobile : null,
                        ],
                        'message' => 'User login successfully.',
                    ], 200);
                }
            }

            else{ 
                return response()->json([
                    'success' => false,
                    'message' => 'User not register.',
                ], 401);
            } 
            
        }
        else
        {
            $request->validate([
                'mobile' => 'required',
                'password' => 'required',
            ]);
    
            if(Auth::attempt(['mobile' => $request->mobile, 'password' => $request->password])){ 
                $user = Auth::user(); 
    
                // Check the user's role
                if ($user->verified == '0') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized.',
                        'errors' => ['error' => 'You do not have access to this section.\nPlease Verify your account first'],
                    ], 403);
                }
    
                // Load the user's manager information (id and mobile)
                $user->load('manager:id,mobile');

                // Generate a Sanctum token
                $token = $user->createToken('API TOKEN')->plainTextToken;
       
                return response()->json([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'name' => $user->name,
                        'role' => $user->role,
                        'manager_mobile_number' => $user->manager ? $user->manager->mobile : null,
                    ],
                    'message' => 'User login successfully.',
                ], 200);
            } 
            else{ 
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                    'errors' => ['error' => 'Unauthorized'],
                ], 401);
            } 
        }
    }

    public function logout(Request $request)
    {
        // Check if the user is authenticated
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'No user is authenticated.',
            ], 401); // 401 Unauthorized
        }

        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 204);
    }

    public function orders(Request $request)
    {
        $get_user = Auth::User();

        if($get_user->role == 'user') {
            $userId = $get_user->id;
        }

        else 
        {
            $request->validate([
                'user_id' => 'required',
            ]);
            $userId = $request->input('user_id');
        }

        $get_product = CartModel::select('amount', 'quantity', 'product_code', 'product_name', 'rate')
                                       ->where('user_id', $userId)
                                       ->get();

        $get_counter_data = CounterModel::select('prefix', 'counter', 'postfix')->where('name', 'order')->get();
       

        // Pad the number with leading zeros to make it a four-digit number
        $formattedCounterNumber = str_pad($get_counter_data[0]->counter, 4, '0', STR_PAD_LEFT);

        if ($get_counter_data->isNotEmpty()) 
        {
            $get_order_id = strtoupper($get_counter_data[0]->prefix).$formattedCounterNumber.$get_counter_data[0]->postfix;
    
            if ((count($get_product)) > 0) 
            {
                $product_amount = 0;
                foreach ($get_product as $order) 
                {
                    $product_amount += (($order->amount) * ($order->quantity));
                }
                
                $create_order = OrderModel::create([
                    'user_id' => $userId,
                    'order_id' => $get_order_id,
                    'order_date' => Carbon::now()->toDateString(),
                    'amount' => $product_amount,
                ]);
                //order_table_id

                if (!is_null($create_order) && isset($create_order->id)) 
                {
                    foreach ($get_product as $order) 
                    {
                        // save every item in order_items with order_table_id
                        $create_order_items = OrderItemsModel::create([
                            'order_id' => $create_order->id,
                            'product_code' => $order->product_code,
                            'product_name' => $order->product_name,
                            'rate' => $order->rate,
                            'quantity' => $order->quantity,
                            'total' => $product_amount,
                        ]);
                    }

                    $update_cart = CounterModel::where('name', 'order_basic')
                                                ->update(['counter' => (($get_counter_data[0]->counter)+1),
                                                ]);

                    // Remove items from the cart for the user
                    $get_remove_items = CartModel::where('user_id', $userId)->delete();
                    
                    $generate_invoice = new InvoiceController();


                    // Generate invoice for $create_order_basic
                    $get_invoice = $generate_invoice->generateInvoice($create_order->id);

                    // Add invoices to the $data array under a specific key
                    $create_order['invoices'] = $get_invoice;
                    unset($create_order['id'], $create_order['created_at'], $create_order['updated_at']);

                    return response()->json([
                        'message' => 'Order created and Order invoice generated successfully!',
                        'data' => $create_order
                    ], 201);

                }

                else {
                    return response()->json([
                        'message' => 'Sorry, failed to place order!',
                        'data' => 'Error!'
                    ], 400);
                }
                
            }

            else {
                return response()->json(['Sorry, no product available!', 'data' => 'Error'], 500);
            }
        }
        
        else {
            return response()->json(['Sorry, something went wrong!', 'data' => 'Error'], 500);
        }   
    }

    public function orders_items(Request $request)
    {
        $request->validate([
            // 'orderID' => 'required',
            'order_id' => 'required',
            // 'item' => 'required',
            'product_code' => 'required',
            'product_name' => 'required',
            'rate' => 'required',
            // 'discount' => 'required',
            'quantity' => 'required',
            // 'line_total' => 'required',
            'total' => 'required',
        ]);

            $create_order_items = OrderItemsModel::create([
                // 'orderID' => $request->input('orderID'),
                'order_id' => $request->input('order_id'),
                // 'item' => $request->input('item'),
                'product_code' => $request->input('product_code'),
                'product_name' => $request->input('product_name'),
                'rate' => $request->input('rate'),
                // 'discount' => $request->input('discount'),
                'quantity' => $request->input('quantity'),
                // 'line_total' => $request->input('line_total'),
                'total' => $request->input('total'),
            ]);


        return isset($create_order_items) && $create_order_items !== null
        ? response()->json(['Order Items created successfully!', 'data' => $create_order_items], 201)
        : response()->json(['Failed to create order items'], 400); 
    }

    public function cart(Request $request)
    {
        $get_user = Auth::User();

        if($get_user->role == 'admin')
        {
            $request->validate([
                'user_id' => 'required',
                'product_code' => 'required',
                'product_name' => 'required',
                'rate' => 'required',
                'quantity' => 'required',
            ]);
        }

        else
        {
            $request->validate([
                'product_code' => 'required',
                'product_name' => 'required',
                'rate' => 'required',
                'quantity' => 'required',
            ]);

            $request->merge(['user_id' => $get_user->id]);
        }
    
            $create_cart = CartModel::updateOrCreate(
                [
                    'user_id' => $request->input('user_id'),
                    'product_code' => $request->input('product_code'),
                ], 
                [
                    'product_name' => $request->input('product_name'),
                    'remarks' => $request->input('remarks'),
                    'rate' => $request->input('rate'),
                    'quantity' => $request->input('quantity'),
                    'amount' => ($request->input('rate')) * ($request->input('quantity')),
                ]
            );

            unset($create_cart['id'], $create_cart['created_at'], $create_cart['updated_at']);


        return isset($create_cart) && $create_cart !== null
        ? response()->json(['Cart created successfully!', 'data' => $create_cart], 201)
        : response()->json(['Failed to create cart successfully!'], 400);

    }

    public function counter(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'prefix' => 'required',
            'counter' => 'required',
            'postfix' => 'required',
        ]);

            $create_counter = CounterModel::create([
                'name' => $request->input('name'),
                'prefix' => $request->input('prefix'),
                'counter' => $request->input('counter'),
                'postfix' => $request->input('postfix'),
            ]);

        unset($create_counter['id'], $create_counter['created_at'], $create_counter['updated_at']);

        return isset($create_counter) && $create_counter !== null
        ? response()->json(['Counter record created successfully!', 'data' => $create_counter], 201)
        : response()->json(['Failed to create counter record'], 400); 
    }

    public function make_invoice(Request $request)
    {
        $create_invoice = InvoiceModel::create([
            'order_id' => $request->input('0.order_id'),
            'user_id' => $request->input('0.user_id'),
            'invoice_number' => $request->input('0.invoice_no'),
            'date' => $request->input('0.invoice_date'),
            'amount' => $request->input('0.amount'),
            'type' => $request->input('0.type'),
        ]);

        $create_invoice_item = InvoiceItemsModel::create([
            'invoice_id' => $create_invoice->id,
            'product_code' => $request->input('0.invoice_items.product_code'),
            'product_name' => $request->input('0.invoice_items.product_name'),
            'quantity' => $request->input('0.invoice_items.quantity'),
            'rate' => $request->input('0.invoice_items.rate'),
            'total' => $request->input('0.invoice_items.total'),
            'type' => $request->input('0.invoice_items.type'),
        ]);

        
        if (isset($create_invoice) && isset($create_invoice_item)) {

            $update_invoice_counter = CounterModel::where('prefix', substr($request->input('0.invoice_no'),0, 7))
            ->increment('counter');

            $templateParams = [
                'name' => 'ace_new_invoice_user', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $create_invoice->user_id,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_invoice->order_id,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_invoice->invoice_number,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_invoice->date,
                            ],
                            [
                                'type' => 'text',
                                'text' => $create_invoice->amount,
                            ],
                        ],
                    ]
                ],
            ];
            
            $whatsAppUtility = new sendWhatsAppUtility();
            
            $response = $whatsAppUtility->sendWhatsApp('+918961043773', $templateParams, '', 'User Invoice');

            return response()->json([
                'message' => 'Insert record successfully!',
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed to insert!',
            ], 400);
        }  
    }
}