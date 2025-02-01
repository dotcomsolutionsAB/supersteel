<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;

use App\Models\CartModel;

use App\Models\OrderModel;

use App\Models\OrderItemsModel;

use App\Utils\sendWhatsAppUtility;

use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;

class UpdateController extends Controller
{
    public function user(Request $request)
    {
        $get_user = Auth::id();

        $request->validate([
            'mobile' => ['required', 'string'],
            'password' => 'required',
            'name' => ['required', 'string'],
            
        ]);

        $update_user_record = User::where('id', $get_user)
        ->update([
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

        return isset($update_user_record) && $update_user_record !== null
        ? response()->json(['User record updated successfully!', 'data' => $update_user_record], 201)
        : response()->json(['Failed to user record'], 400);
    }

    public function user_password(Request $request)
    {
        $get_user = Auth::id();

        $request->validate([
            'password' => ['required', 'string'],  
        ]);

        $update_user_password = User::where('id', $get_user)
        ->update([
            'password' => bcrypt($request->input('password')),
        ]);

        return isset($update_user_password) && $update_user_password !== null
        ? response()->json(['User password updated successfully!', 'data' => $update_user_password], 201)
        : response()->json(['Failed to user password'], 400);
    }


    public function generate_otp(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'size:13'],
        ]);

        $mobile = $request->input('mobile');

        $get_user = User::select('id')
            ->where('mobile', $mobile)
            ->first();
            
        if (!$get_user == null) {

            $six_digit_otp_number = random_int(100000, 999999);

            $expiresAt = now()->addMinutes(10);

            $store_otp = User::where('mobile', $mobile)
                ->update([
                    'otp' => $six_digit_otp_number,
                    'expires_at' => $expiresAt,
                ]);
            
            if ($store_otp) {

                $templateParams = [
                    'name' => 'ss_otp', // Replace with your WhatsApp template name
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $six_digit_otp_number,
                                ],
                            ],
                        ],
                        [
                            'type' => 'button',
                            'sub_type' => 'url',
                            "index" => "0",
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $six_digit_otp_number,
                                ],
                            ],
                        ]
                    ],
                ];
                
                // Directly create an instance of SendWhatsAppUtility
                $whatsAppUtility = new sendWhatsAppUtility();
                
                // Send OTP via WhatsApp
                $response = $whatsAppUtility->sendWhatsApp($mobile, $templateParams, $mobile, 'OTP Campaign');
                
                return response()->json([
                    'message' => 'Otp store successfully!',
                    'data' => $store_otp
                ], 200);
            }

            else {
                return response()->json([
                    'message' => 'Fail to store otp successfully!',
                    'data' => $store_otp
                ], 501);
            }
        }

        else {
            return response()->json([
                'success' => false,
                'message' => 'Mobile no. is not registered.',
            ], 200);
        }
    }

    public function cart(Request $request, $id)
    {
        $request->validate([
            'product_code' => 'required|exists:t_products,product_code',
            'quantity' => 'required|numeric',
        ]);

        $update_cart = CartModel::where('id', $id)
                                ->update([
                'product_code' => $request->input('product_code'), 
                'product_name' => $request->input('product_name'),
                'remarks' => $request->input('remarks'),
                'rate' => $request->input('rate'),
                'quantity' => $request->input('quantity'),
                'amount' => ($request->input('rate')) * ($request->input('quantity')),
            ]);
 
        return isset($update_cart) && $update_cart !== null
        ? response()->json(['Cart updated successfully!', 'data' => $update_cart], 200)
        : response()->json(['Failed to update cart'], 404); 
    }

    public function verify_user(Request $request, $get_id)
    {
        $request->validate([
            'price_type' => 'required|string',
            'manager_id' => 'required|integer',
        ]);
        $update_verify = User::where('id', $get_id)
            ->update([
                'is_verified' => '1',
                'price_type' => $request->input('price_type'),
                'manager_id' => $request->input('manager_id')
            ]);

            $user = User::select('name', 'mobile')
                         ->where('id', $get_id)
                         ->first();

            if(!$user)
            {
                return response()->json(['Sorry, user not found!'], 404); 
            }

            if ($update_verify == 1) {

                $templateParams = [
                    'name' => 'ss_user_approved', // Replace with your WhatsApp template name
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $user->name,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => substr($user->mobile, -10),
                                ],
                            ],
                        ]
                    ],
                ];
                
                // Directly create an instance of SendWhatsAppUtility
                $whatsAppUtility = new sendWhatsAppUtility();
                
                // Send OTP via WhatsApp
                $response = $whatsAppUtility->sendWhatsApp('+918961043773', $templateParams, '', 'Approve Client');
                
                return response()->json([
                    'message' => 'User verified successfully!',
                    'data' => $update_verify
                ], 200);
            }
    
            else {
                return response()->json([
                    'message' => 'Failed to verify the user'
                ], 400);
            }    
    }

    public function unverify_user($get_id)
    {
        $update_unverify = User::where('id', $get_id)
            ->update([
                'is_verified' => '0',
            ]);

            $user = User::select('name', 'mobile')
                         ->where('id', $get_id)
                         ->first();
            
            if(!$user)
            {
                return response()->json(['Sorry, user not found!'], 404); 
            }

            if ($update_unverify == 1) {

                $templateParams = [
                    'name' => 'ss_user_approved', // Replace with your WhatsApp template name
                    'language' => ['code' => 'en'],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $user->name,
                                ],
                                [
                                    'type' => 'text',
                                    'text' => substr($user->mobile, -10),
                                ],
                            ],
                        ]
                    ],
                ];
                
                // Directly create an instance of SendWhatsAppUtility
                $whatsAppUtility = new sendWhatsAppUtility();
                
                // Send OTP via WhatsApp
                $response = $whatsAppUtility->sendWhatsApp('+918961043773', $templateParams, '', 'Approve Client');
                
                return response()->json([
                    'message' => 'User un-verified successfully!',
                    'data' => $update_unverify
                ], 200);
            }
    
            else {
                return response()->json([
                    'message' => 'Failed to un-verify the user'
                ], 400);
            }    
    }

    // update order
    public function order(Request $request, $id)
    {
        // Validate incoming request data
        $request->validate([
            'order_id' => 'required|string',
            'user_id' => 'required|integer',
            'amount' => 'required|numeric',
            'items' => 'required|array',
            'items.*.product_code' => 'required|string|exists:t_products,product_code',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer',
            'items.*.rate' => 'required|numeric',
            'items.*.total' => 'required|numeric',
            'items.*.remarks' => 'nullable|string',
        ]);

        // Find the order by its ID
        $order = OrderModel::where('id',$id)
                            ->where('user_id', $request->input('user_id'))
                            ->first();

        if ($order == null) {
            return response()->json([
                'message' => 'Order not found!'
            ], 404);
        }

        // // Check if the order belongs to the provided user_id
        // if ($order->user_id !== $request->input('user_id')) {
        //     return response()->json([
        //         'message' => 'Unauthorized action. This order does not belong to the specified user.'
        //     ], 403);
        // }

        // Update the order details
        $order->amount = $request->input('amount');
        $order->save();

        // Remove existing order items for the given order ID
        OrderItemsModel::where('order_id', $id)->delete();

        // Add the updated items to the order
        $items = $request->input('items');
        foreach ($items as $item) {
            OrderItemsModel::create([
                'order_id' => $id,
                'product_code' => $item['product_code'],
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'rate' => $item['rate'],
                'total' => $item['total'],
                'remarks' => $item['remarks'] ?? '',
            ]);
        }

        if($order->type == 'order')
        {
            $generate_invoice_zp = new InvoiceControllerZP();
            $generate_invoice_zp->generateorderInvoiceZP($id, true, 'order');
        }else {
            $generate_order_invoice = new InvoiceController();
            $generate_order_invoice->generateInvoice($id, true, 'quotation');
        }

        return response()->json([
            'message' => 'Order updated successfully!',
            'order' => $order,
            'items' => $items
        ], 200);
    }

    public function partial_order(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'order_id' => 'required|string',
            'user_id' => 'required|integer'
        ]);

        // Find the order by its ID
        // $order = OrderModel::find($id);
        $order = OrderModel::where('id',$request->input('order_id'))
                            ->where('user_id', $request->input('user_id'))
                            ->first();

        if ($order == null) {
            return response()->json([
                'message' => 'Order not found!'
            ], 404);
        }

        // Update the status of the order to 'completed'
        $order->status = 'partial';
        $order->save();

        unset($order['id'], $order['created_at'], $order['updated_at']);

        return response()->json([
            'message' => 'Order status updated to partial successfully!',
            'order' => $order
        ], 200);
    }

    public function paid_order(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'order_id' => 'required|string',
            'user_id' => 'required|integer'
        ]);

        // Find the order by its ID
        // $order = OrderModel::find($id);
        $order = OrderModel::where('id',$request->input('order_id'))
                            ->where('user_id', $request->input('user_id'))
                            ->first();

        if ($order == null) {
            return response()->json([
                'message' => 'Order not found!'
            ], 404);
        }

        // Update the status of the order to 'completed'
        $order->status = 'paid';
        $order->save();

        unset($order['id'], $order['created_at'], $order['updated_at']);

        return response()->json([
            'message' => 'Order status updated to paid successfully!',
            'order' => $order
        ], 200);
    }

    public function complete_order(Request $request, $id)
    {
        // Validate incoming request data
        $request->validate([
            'order_id' => 'required|string',
            'user_id' => 'required|integer'
        ]);

        // Find the order by its ID
        $order = OrderModel::find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found!'
            ], 404);
        }

        // Check if the order belongs to the provided user_id
        if ($order->user_id !== $request->input('user_id')) {
            return response()->json([
                'message' => 'Unauthorized action. This order does not belong to the specified user.'
            ], 403);
        }

        // Update the status of the order to 'completed'
        $order->status = 'completed';
        $order->save();

        return response()->json([
            'message' => 'Order status updated to completed successfully!',
            'order' => $order
        ], 200);
    }

    public function cancel_order(Request $request, $id)
    {
        // Validate incoming request data
        $request->validate([
            'order_id' => 'required|string',
            'user_id' => 'required|integer'
        ]);

        // Find the order by its ID
        $order = OrderModel::find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found!'
            ], 404);
        }

        // Check if the order belongs to the provided user_id
        if ($order->user_id !== $request->input('user_id')) {
            return response()->json([
                'message' => 'Unauthorized action. This order does not belong to the specified user.'
            ], 403);
        }

        // Update the status of the order to 'cancelled'
        $order->status = 'cancelled';
        $order->save();

        $user = User::find($order->user_id);
        $mobileNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();

        $whatsAppUtility = new sendWhatsAppUtility();

        $templateParams = [
            'name' => 'ss_order_cancelled_3', // Replace with your WhatsApp template name
            'language' => ['code' => 'en'],
            'components' => [[
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $user->name,
                        ],
                        [
                            'type' => 'text',
                            'text' => $order->order_id,
                        ],
                        [
                            'type' => 'text',
                            'text' => Carbon::parse($order->order_date)->format('d-m-Y'),
                        ],
                    ],
                ]
            ],
        ];

        foreach ($mobileNumbers as $mobileNumber) 
        {
            if($mobileNumber == '+917003541353' || true)
            {
                // Send message for each number
                $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Order Cancel Notification');
            }
        }

        $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'Order Cancel Notification');

        return response()->json([
            'message' => 'Order status updated to cancelled successfully!',
            'order' => $order
        ], 200);
    }
}