<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use App\Models\ProductModel;

use App\Models\User;

use App\Models\OrderModel;

use App\Models\OrderItemsModel;

use App\Models\CartModel;

use App\Models\CounterModel;

use App\Models\CategoryModel;

use App\Models\SubCategoryModel;

use DB;

class ViewController extends Controller
{
    //
    public function product()
    {
        $get_product_details = ProductModel::select('product_code', 'product_name', 'print_name', 'brand', 'c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'type', 'machine_part_no', 'price_a','price_b','price_c', 'price_d', 'price_e', 'product_image')->get();
        

        return isset($get_product_details) && $get_product_details !== null
        ? response()->json(['Fetch records successfully!', 'data' => $get_product_details, 'fetch_records' => count($get_product_details)], 200)
        : response()->json(['Failed get data'], 404); 
    }

    // public function lng_product($lang = 'eng')
    // {
    //     $get_product_details = ProductModel::select('product_code','product_name', 'print_name', 'name_in_hindi','name_in_telugu', 'brand', 'category', 'category_lvl2', 'category_lvl3', 'category_lvl4', 'category_lvl5', 'category_lvl6', 'type', 'machine_part_no', 'price_a','price_b','price_c', 'price_d', 'price_e')->get();
        
    //     $processed_prd_rec = $get_product_details->map(function($prd_rec) use ($lang)
    //     {
    //         $product_name = $prd_rec->product_name;

    //         if($lang === 'hin' && !empty($prd_rec->name_in_hindi))
    //         {
    //             $product_name = $prd_rec->name_in_hindi;
    //         }

    //         elseif ($lang === 'tlg' && !empty($prd_rec->name_in_telugu)) {
    //             $product_name = $prd_rec->name_in_telugu;
    //         }

    //         return [
    //             'product_code' => $prd_rec->product_code,
    //             'product_name' => $product_name,
    //             'print_name' => $prd_rec->print_name,
    //             'brand' => $prd_rec->brand,
    //             'category' => $prd_rec->category,
    //             'category_lvl2' => $prd_rec->category_lvl2,
    //             'category_lvl3' => $prd_rec->category_lvl3,
    //             'category_lvl4' => $prd_rec->category_lvl4,
    //             'category_lvl5' => $prd_rec->category_lvl5,
    //             'type' => $prd_rec->type,
    //             'machine_part_no' => $prd_rec->machine_part_no,
    //             'price_a' => $prd_rec->price_a,
    //             'price_b' => $prd_rec->price_b,
    //             'price_c' => $prd_rec->price_c,
    //             'price_d' => $prd_rec->price_d,
    //             'price_e' => $prd_rec->price_e,
    //         ];
    //     });


    //     return isset($processed_prd_rec) && $processed_prd_rec !== null
    //     ? response()->json(['Fetch data successfully!', 'data' => $processed_prd_rec], 201)
    //     : response()->json(['Failed to get data'], 400);     
    // }

    public function get_product(Request $request)
    {

        $user_price = Auth::user()->price_type;

        // Retrieve offset and limit from the request with default values
        $offset = $request->input('offset', 0); // Default to 0 if not provided
        $limit = $request->input('limit', 10);  // Default to 10 if not provided
        $user_id = $request->input('user_id');  // Assuming the user ID is provided in the request

        // Ensure the offset and limit are integers and non-negative
        $offset = max(0, (int) $offset);
        $limit = max(1, (int) $limit);

        // Retrieve filter parameters if provided
        $search = $request->input('search', null);
        

        // Initialize the default query
        $query = ProductModel::query();

        // Determine the column to select based on the user's price type

        $price_column = '';

        switch($user_price)
        {
            case 'a':
                $price_column = 'price_a';
                break;
            case 'b':
                $price_column = 'price_b';
                break;
            case 'c':
                $price_column = 'price_c';    
                break;
            case 'd':
                $price_column = 'price_d';
                break;
            case 'e':
                $price_column = 'price_e';
            // Add more cases as needed
            default:
            // In case of no matching price type, select all price columns
                $query->select('id', 'product_code', 'product_name', 'print_name', 'brand', 'c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'type', 'machine_part_no', 'price_a','price_b','price_c', 'price_d', 'price_e', 'product_image');
                break;
        }

        // If a valid price type is found, select that column as 'price'
        if (!empty($price_column)) {
            $query->select('id', 'product_code', 'product_name', 'print_name', 'brand', 'c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'type', 'machine_part_no', DB::raw("$price_column as price"), 'product_image');
        }


        // Apply search filter if provided
        if ($search) {
            $query->where('product_name', 'like', "%{$search}%");
        }

        // Apply pagination
        $query->skip($offset)->take($limit);
        $get_products = $query->get();

        // Check if products are found
        if (isset($get_products) && !$get_products->isEmpty()) {

            // Loop through each product to check if it's in the cart
            foreach ($get_products as $product) {
                // Check if the product is in the user's cart
                $cart_item = CartModel::where('user_id', $user_id)
                    ->where('product_code', $product->product_code)
                    ->first();

                // If the product is in the cart, set cart details
                // if ($cart_item) {
                //     $product->in_cart = true;
                //     $product->cart_quantity = $cart_item->quantity;
                //     $product->cart_type = $cart_item->type;
                // } else {
                //     // If the product is not in the cart
                //     $product->in_cart = false;
                //     $product->cart_quantity = null;  // or 0, depending on your preference
                //     $product->cart_type = null;
                // }
            }

            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_products
            ], 200);

        } else {
            return response()->json([
                'message' => 'Failed to fetch data!',
            ], 400);
        }
    }

    // public function lng_get_product(Request $request, $lang = 'eng')
    // {
    //     // Retrieve input parameters with defaults
    //     $offset = max(0, (int) $request->input('offset', 0));
    //     $limit = max(1, (int) $request->input('limit', 10));
    //     $user_id = $request->input('user_id');
    //     $search = $request->input('search', null);
    //     $category = $request->input('category', null);
    //     // $subCategory = $request->input('sub_category', null);

    //     // Build the query for products
    //     $query = ProductModel::select(
    //         'product_code', 'product_name', 'name_in_hindi', 'name_in_telugu', 'brand', 'category', 'category_lvl2', 'category_lvl3', 'category_lvl3', 'category_lvl4', 'category_lvl5','type','machine_part_no', 'price_a', 'price_b', 'price_c', 'price_d', 'price_e'
    //     );

    //     // Apply filters
    //     if ($search) {
    //         $query->where('product_name', 'like', "%{$search}%");
    //     }
    //     if ($category) {
    //         $query->where('category', $category);
    //     }
    //     // if ($subCategory) {
    //     //     $query->where('sub_category', $subCategory);
    //     // }

    //     // Apply pagination and get products
    //     $get_products = $query->skip($offset)->take($limit)->get();

    //     // Process products for language and cart details
    //     $processed_prd_lang_rec = $get_products->map(function ($prd_rec) use ($lang, $user_id) {
    //         // Set product name based on the selected language
    //         $product_name = $prd_rec->product_name;
    //         if ($lang === 'hin' && !empty($prd_rec->name_in_hindi)) {
    //             $product_name = $prd_rec->name_in_hindi;
    //         } elseif ($lang === 'tlg' && !empty($prd_rec->name_in_telugu)) {
    //             $product_name = $prd_rec->name_in_telugu;
    //         }

    //         // Check if the product is in the user's cart
    //         $cart_item = CartModel::where('user_id', $user_id)
    //             ->where('product_code', $prd_rec->product_code)
    //             ->first();

    //         // Return processed product data
    //         return [
    //             'product_code' => $prd_rec->product_code,
    //             'product_name' => $product_name,
    //             'brand' => $prd_rec->brand,
    //             'category' => $prd_rec->category,
    //             'category_lvl2' => $prd_rec->category_lvl2,
    //             'category_lvl3' => $prd_rec->category_lvl3,
    //             'category_lvl4' => $prd_rec->category_lvl4,
    //             'category_lvl5' => $prd_rec->category_lvl5,
    //             'type' => $prd_rec->type,
    //             'machine_part_no' => $prd_rec->machine_part_no,
    //             'price_a' => $prd_rec->price_a,
    //             'price_b' => $prd_rec->price_b,
    //             'price_c' => $prd_rec->price_c,
    //             'price_d' => $prd_rec->price_d,
    //             'price_e' => $prd_rec->price_e,
    //             'in_cart' => $cart_item ? true : false,
    //             'cart_quantity' => $cart_item->quantity ?? null,
    //             'cart_type' => $cart_item->type ?? null,
    //         ];
    //     });

    //     // Return response based on the result
    //     return $processed_prd_lang_rec->isEmpty()
    //     ? response()->json(['Failed to fetch data!'], 400)
    //     : response()->json(['message' => 'Fetch data successfully!',
    //             'data' => $processed_prd_lang_rec,
    //             'count' => count($processed_prd_lang_rec)], 201);
    // }

    public function get_spares($code = null)
    {
        $productQuery = ProductModel::select('product_code','product_name','category','product_image')
                                            ->where('type', 'SPARE');
        

        if ($code !== null) {
            $productQuery->where('machine_part_no', 'like', "%{$code}%");
        }

        $get_spare_product = $productQuery->get();

        return isset($get_spare_product) && $get_spare_product !== null
        ? response()->json(['Fetch data successfully!', 'data' => $get_spare_product, 'fetch_records' => count($get_spare_product)], 200)
        : response()->json(['Failed get data'], 404); 
    }

    public function categories(Request $request)
    {
        $query = ProductModel::query();
        
        if(empty($request->c1) && empty($request->c2) && empty($request->c3))
        {
            $distinctValues = $query->get(); 
                             
        }
        elseif (!empty($request->c1) && empty($request->c2) && empty($request->c3)) 
        {
            $distinctValues = $query
                            ->where('c1', $request->c1)
                            ->distinct()
                            ->pluck('c2');                  
        }
        elseif (!empty($request->c1) && !empty($request->c2) && empty($request->c3)) 
        {
            $distinctValues = $query
                            ->where('c1', $request->c1)
                            ->where('c2', $request->c2)
                            ->distinct()
                            ->pluck('c3');                  
        }
        elseif (!empty($request->c1) && !empty($request->c2) && !empty($request->c3)) 
        {
            $distinctValues = $query
                            ->where('c1', $request->c1)
                            ->where('c2', $request->c2)
                            ->where('c3', $request->c3)
                            ->distinct()
                            ->pluck('c4');                  
        }
        else
        {
            $distinctValues = $query
                            ->where('c1', $request->c1)
                            ->where('c2', $request->c2)
                            ->where('c3', $request->c3)
                            ->where('c4', $request->c4)
                            ->distinct()
                            ->pluck('c5');    
        }

        return $distinctValues->isEmpty() 
        ? response()->json(['Sorry, Failed to get data'], 404)
        : response()->json(['Fetch data successfully!', 'data' => $distinctValues], 200);
    }
    // public function categories()
    // {
    //     // Fetch all categories with their product count
    //     $categories = CategoryModel::withCount('get_products')->get();

    //     // Format the categories data for a JSON response
    //     $formattedCategories = $categories->map(function ($category) {
    //         return [
    //             'category_id' => $category->id,
    //             'category_name' => $category->name,
    //             'category_image' => $category->image,
    //             'products_count' => $category->get_products_count,
    //         ];
    //     });

    //     if (isset($formattedCategories)) {
    //         return response()->json([
    //             'message' => 'Fetch data successfully!',
    //             'data' => $formattedCategories,
    //             'count' => count($formattedCategories),
    //         ], 201);
    //     }

    //     else {
    //         return response()->json([
    //             'message' => 'Failed get data successfully!',
    //         ], 400);
    //     }    
    // }

    // public function lng_categories($lang = 'eng')
    // {
    //     // Fetch all categories with their product count
    //     $categories = CategoryModel::withCount('get_products')->get();

    //     // Format the categories data for a JSON response
    //     $formattedCategories = $categories->map(function ($category) use ($lang) {

    //         $category_name = $category->name;

    //         if($lang === 'hin' && !empty($category->name_in_hindi))
    //         {
    //             $category_name = $category->name_in_hindi;
    //         }

    //         elseif ($lang === 'tlg' && !empty($category->name_in_telugu)) 
    //         {
    //             $category_name = $category->name_in_telugu;
    //         }
    //         return [
    //             'category_id' => $category->id,
    //             'category_name' => $category_name,
    //             'category_image' => $category->image,
    //             'products_count' => $category->get_products_count,
    //         ];
    //     });
    //     // Check if the categories are set and return response
    //     return $formattedCategories->isEmpty()
    //     ? response()->json(['Failed get data successfully!'], 400)
    //     : response()->json(['message' => 'Fetch data successfully!',
    //             'data' => $formattedCategories,
    //             'count' => count($formattedCategories)], 201);
    // }

    public function user()
    {
        $userRole = (Auth::user())->role;

        if ($userRole == 'admin') 
        {
        
            $get_user_details = User::with('manager:id,mobile')
                                ->select('id','name', 'email','mobile','role','address_line_1','address_line_2','city','pincode','gstin','state','country','manager_id')
                                ->where('role', 'user')
                                ->get();

            $response = [];

            foreach($get_user_details as $user)
            {
                $response[] = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'address_line_1' => $user->address_line_1,
                    'address_line_2' => $user->address_line_2,
                    'city' => $user->city,
                    'pincode' => $user->pincode,
                    'gstin' => $user->gstin,
                    'state' => $user->state,
                    'country' => $user->country,
                    'manager_phone' => $user->manager ? $user->manager->mobile : null,
                ];
            }
        }

        elseif ($userRole == 'manager') 
        {
            $get_user_details = User::select('id','name', 'email','mobile','role','address_line_1','address_line_2','city','pincode','gstin','state','country')
                                    ->where('manager_id', Auth::id())
                                    ->get();

            $response = $get_user_details->map(function ($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'address_line_1' => $user->address_line_1,
                    'address_line_2' => $user->address_line_2,
                    'city' => $user->city,
                    'pincode' => $user->pincode,
                    'gstin' => $user->gstin,
                    'state' => $user->state,
                    'country' => $user->country,
                ];
            });
            
        }

        return empty($response)
        ? response()->json(['Sorry, Failed to get data'], 404)
        : response()->json(['Fetch data successfully!', 'data' => $response], 200);
    }

    public function user_details()
    {
        $get_user_id = Auth::id();
        
        $get_user_details = User::select('id','name','email','mobile','address_line_1','address_line_2','city','pincode','gstin','state','country')->where('id', $get_user_id)->get();
        

        return isset($get_user_details) && $get_user_details !== null
        ? response()->json(['Fetch data successfully!', 'data' => $get_user_details], 201)
        : response()->json(['Failed to fetch data'], 400); 
    }

    public function orders()
    {
        $get_all_orders = OrderModel::with('user')->get();
        
        $formatted_order = $get_all_orders->map(function ($order_rec)
        {
            $order_rec_arr = $order_rec->toArray();
            unset($order_rec_arr['created_at'], $order_rec_arr['updated_at']);
            return $order_rec_arr;
        });

        return isset($formatted_order) && $formatted_order !== null
        ? response()->json(['Fetch data successfully!', 'data' => $formatted_order], 200)
        : response()->json(['Sorry, Failed to fetch data'], 404);

    }

    public function orders_user_id($id = null)
    {
        // Fetch all records if $id is null, otherwise filter by user_id
        $get_user_orders = OrderModel::when($id, function($query, $id)
        {
            // If $id is not null, filter by user_id
            return $query->where('user_id', $id);
            
        })->get();   

        $formatted_user_order = $get_user_orders->map(function ($order_user_rec)
        {
            $order_user_rec_arr = $order_user_rec->toArray();
            unset($order_user_rec_arr['created_at'], $order_user_rec_arr['updated_at']);
            return $order_user_rec_arr;
        });

        return isset($order_user_rec_arr) && $order_user_rec_arr !== null
        ? response()->json(['Fetch data successfully!', 'data' => $order_user_rec_arr, 'fetch_records' => count($order_user_rec_arr)], 200)
        : response()->json(['Failed get data successfully!'], 400);
    }

    public function order_items()
    {
        $get_all_order_items = OrderItemsModel::with('get_orders')->get();
        

        if (isset($get_all_order_items)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_all_order_items
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function orders_items_order_id($id)
    {
        $get_items_for_orders = OrderItemsModel::where('orderID', $id)->get();

        return isset($get_items_for_orders) && $get_items_for_orders !== null
        ? response()->json(['Fetch data successfully!', 'data' => $get_items_for_orders], 201)
        : response()->json(['Failed to get data'], 400);  
    }

    public function cart()
    {
        // Retrieve all records with their associated user and product data
        $get_all_cart_records = CartModel::with(['get_users', 'get_products'])->get();
        

        // Transform the data if needed
        $formattedCartData = $get_all_cart_records->map(function ($item) {
			
            return [
                'id' => $item->id, // Adjust as necessary
                'user' => $item->get_users ? [
                    'id' => $item->get_users->id,
                    'name' => $item->get_users->name, // Adjust fields as necessary
                ] : null,
                'product' => $item->get_products ? [
                    'product_code' => $item->get_products->product_code,
                    'name' => $item->get_products->product_name, // Adjust fields as necessary
                ] : null,
            ];
        });

        return isset($formattedCartData) && $formattedCartData !== null
        ? response()->json(['Fetch all recods successfully!', 'data' => $create_cart], 201)
        : response()->json(['Failed fetch records successfully!'], 400);
    }

    public function cart_user($id = null)
    {
        $get_user = Auth::User();

        if($get_user->role == 'admin')
        {
            //$get_items_for_user = CartModel::where('user_id', $id)->get();
			$get_items_for_user = CartModel::where('t_cart.user_id', $id)
				->join('t_products', 't_cart.product_code', '=', 't_products.product_code')
				->select(
					't_cart.id',
					't_cart.user_id',
					't_cart.product_code',
					't_cart.product_name',
					't_cart.rate',
					't_cart.quantity',
					't_cart.amount',
					// 't_cart.created_at',
					// 't_cart.updated_at',
					// 't_products.basic',
					// 't_products.gst',
					// 't_products.product_image'
				)
				->get();
        }

        else {
			$get_items_for_user = CartModel::where('t_cart.user_id', $get_user->id)
				->join('t_products', 't_cart.product_code', '=', 't_products.product_code')
				->select(
					't_cart.id',
					't_cart.user_id',
					't_cart.product_code',
					't_cart.product_name',
					't_cart.rate',
					't_cart.quantity',
					't_cart.amount',
					// 't_cart.created_at',
					// 't_cart.updated_at',
					// 't_products.basic',
					// 't_products.gst',
					// 't_products.product_image'
				)
				->get();
        }
        

        return isset($get_items_for_user) && $get_items_for_user->isNotEmpty()
        ? response()->json(['Fetch data successfully!', 'data' => $get_items_for_user, 'record count' => count($get_items_for_user)], 201)
        : response()->json(['Sorry, your cart is empty'], 400);  
    }

    public function counter()
    {
        $get_counter_records = CounterModel::all();


        // Iterate over each record, convert it to an array, and remove the unwanted fields
        $filtered_records = $get_counter_records->map(function ($record) {
            $record_array = $record->toArray(); // Convert model to array
            unset($record_array['id'], $record_array['created_at'], $record_array['updated_at']); // Remove fields
            return $record_array; // Return the modified array
        });

        return isset($filtered_records) && $filtered_records !== null
        ? response()->json(['Fetch data successfully!', 'data' => $filtered_records, 'fetch_record' =>count($filtered_records)], 200)
        : response()->json(['Failed to get data'], 400); 
    }

    public function dashboard_details()
    {
        $get_product_numbers = ProductModel::count();
        $get_user_numbers = User::count();
        $get_order_numbers = OrderModel::count();

        $get_dashboard_records = array([
            'total_users' => $get_user_numbers,
            'total_products' => $get_product_numbers,
            'total_orders' => $get_order_numbers,
        ]);

        return isset($get_dashboard_records) && $get_dashboard_records !== null
        ? response()->json(['Fetch records successfully!', 'data' => $get_dashboard_records], 200)
        : response()->json(['Sorry, failed fetch records'], 400);
    }

    public function return_order($orderId)
    {
        $get_order_details = OrderModel::with('order_items')
                                        ->where('id', $orderId)
                                        ->get();


        if ($get_order_details) 
        {
                $get_invoice_id = CounterModel::where('name', 'invoice')
                                                ->get();

                $return_invoice_id = $get_invoice_id[0]->prefix.$get_invoice_id[0]->counter.$get_invoice_id[0]->postfix;

            $formatted_order_record = 
            [
                'id' => $get_order_details[0]->id,
                'order_id' => $get_order_details[0]->order_id,
                'user_id' => $get_order_details[0]->user_id,
                'order_date' => $get_order_details[0]->order_date ? $get_order_details[0]->order_date : null,
                'amount' => $get_order_details[0]->amount,
                'status' => $get_order_details[0]->status,
                'type' => ucfirst($get_order_details[0]->type),
                'order_invoice' => $get_order_details[0]->order_invoice,
                'order_invoice_id' => $return_invoice_id,
                'order_items' => $get_order_details[0]->order_items->map(function ($item) {
                    return 
                    [
                        'product_code' => $item->product_code,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'rate' => $item->rate,
                        'type' => ucfirst($item->type ?? '')  
                    ];
                })->toArray()
            ];
        }   
        return isset($formatted_order_record) && $formatted_order_record !== null
        ? response()->json(['Fetch records successfully!', 'data' => $formatted_order_record], 200)
        : response()->json(['Failed to get order records!'], 400);
    }
}