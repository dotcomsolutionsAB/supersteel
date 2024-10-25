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

use App\Models\AppCategoryModel;

use App\Models\AppSubCategoryModel;

use DB;

class ViewController extends Controller
{
    //
    public function product()
    {
        $get_product_details = ProductModel::select('product_code', 'product_name', 'print_name', 'brand', 'c1', 'c2', 'c3', 'c4', 'c5', 'type', 'machine_part_no', 'price_a','price_b','price_c', 'price_d', 'price_i', 'product_image')->get();
        

        return isset($get_product_details) && $get_product_details !== null
        ? response()->json(['Fetch records successfully!', 'data' => $get_product_details, 'fetch_records' => count($get_product_details)], 200)
        : response()->json(['Failed get data'], 404); 
    }

    public function get_product(Request $request)
    {

        $user_price = Auth::user()->price_type;

        // Retrieve offset and limit from the request with default values
        $offset = $request->input('offset', 0); // Default to 0 if not provided
        $limit = $request->input('limit', 10);  // Default to 10 if not provided

        $get_user = Auth::User();

        if ($get_user->role == 'user') {
            $user_id = $get_user->id;
        } else {
            $request->validate([
                'user_id' => 'required',
            ]);
            $user_id = $request->input('user_id');
        }
        // Ensure the offset and limit are integers and non-negative
        $offset = max(0, (int) $offset);
        $limit = max(1, (int) $limit);

        // Retrieve filter parameters if provided
        $search = $request->input('search', null);
		
		$user_price_type = User::select('price_type')
                                ->where('id', $user_id)
                                ->get();
        
		$price_type = $user_price_type[0]['price_type'];
		//die($price_type);
        // Initialize the default query
        $query = ProductModel::query();

        // Determine the column to select based on the user's price type

        $price_column = '';

        switch($price_type)
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
            case 'i':
                $price_column = 'price_i';
				break;
            // Add more cases as needed
            default:
            // In case of no matching price type, select all price columns
                $price_column = 'price_a';
                break;
        }

        // If a valid price type is found, select that column as 'price'
        if (!empty($price_column)) {
            $query->select('id', 'product_code', 'product_name', 'print_name', 'brand', 'category', 'sub_category', 'type', 'machine_part_no', DB::raw("$price_column as price"), 'product_image', 'new_arrival','special_price');
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

                // Check if the product code appears in other product's machine_part_no
                $has_spares = ProductModel::where('machine_part_no', 'like', "%{$product->product_code}%")
                ->where('product_code', '!=', $product->product_code) // Exclude the current product
                ->exists();

                // Set the has_spares field in the product response
                $product->has_spares = $has_spares;

                // If the product is in the cart, set cart details
                if ($cart_item) {
                    $product->in_cart = true;
                    $product->cart_quantity = $cart_item->quantity;
                    $product->cart_remarks = $cart_item->remarks;
                } else {
                    // If the product is not in the cart
                    $product->in_cart = false;
                    $product->cart_quantity = null;  // or 0, depending on your preference
                    $product->cart_remarks = null;  // or 0, depending on your preference
                }
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
	
    public function get_spares(Request $request, $code = null)
    {
		$user_id = $request->input('user_id');  // Assuming the user ID is provided in the request
		
		$user_price_type = User::select('price_type')
                                ->where('id', $user_id)
                                ->get();
        
		$price_type = $user_price_type[0]['price_type'];
        // Initialize the default query
        $query = ProductModel::query();

        // Determine the column to select based on the user's price type

        $price_column = '';

        switch($price_type)
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
            case 'i':
                $price_column = 'price_i';
				break;
            // Add more cases as needed
            default:
            // In case of no matching price type, select all price columns
                $price_column = 'price_a';
                break;
        }
		
        $productQuery = ProductModel::select('product_code','product_name','c1','c2','c3','c4','c5',DB::raw("$price_column as price"), 'product_image')->where('product_code', '!=', "{$code}");
        
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
        // Fetch all categories with their product count
        $categories = AppCategoryModel::withCount('get_products')->get();

        // Filter and format the categories data for a JSON response
        $formattedCategories = $categories->map(function ($category) {
            // Only include categories with products_count > 0
            if ($category->get_products_count > 0) {
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_image' => $category->category_image,
                    'products_count' => $category->get_products_count,
                ];
            }
            return null; // Return null for categories with 0 products
        })->filter(); // Remove null values

        // Add slides object with links to images in the storage folder
        $slides = [
            asset('/storage/uploads/slider/slide_01.jpg')
        ];

        // Add the two new items: "New Arrival" and "Special Offer"
        $newArrivals = [
            'category_id' => 'new_arrival', // Can use an ID if applicable
            'category_name' => 'New Arrival',
            'category_image' => asset('/storage/uploads/category/new_arrival.png'),
            'products_count' => 10 // Example count, adjust as needed
        ];

        $specialOffers = [
            'category_id' => 'special_offer', // Can use an ID if applicable
            'category_name' => 'Special Offer',
            'category_image' => asset('/storage/uploads/category/special_offer.png'),
            'products_count' => 5 // Example count, adjust as needed
        ];

        // Append new items to the categories
        $formattedCategories->push($newArrivals);
        $formattedCategories->push($specialOffers);

        if (isset($formattedCategories)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $formattedCategories,
                'count' => count($formattedCategories),
                'slides' => $slides, // Add slides to the response
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to get data successfully!',
            ], 404);
        }
    }


	public function sub_category(Request $request)
    {
        // Convert the string of category IDs to an array, e.g., '1,2' -> [1, 2]
        $category = $request['category'];
        $categoryIds = $category ? explode(',', $category) : [];

        // Fetch subcategories filtered by multiple category_ids if provided
        $sub_categories = AppSubCategoryModel::withCount('products')
            ->when(!empty($categoryIds), function ($query) use ($categoryIds) {
                // Filter subcategories by multiple category_ids using whereIn
                return $query->whereIn('category_id', $categoryIds);
            })->get();

        // Format the categories data for a JSON response
        $formattedSubCategories = $sub_categories->map(function ($sub_category) {
            return [
                'sub_category_name' => $sub_category->name,
                'sub_category_image' => $sub_category->category_image,
                'sub_products_count' => $sub_category->products_count,
            ];
        });

        // Apply custom sorting: Spares first, Accessories second, then the rest
        $orderedSubCategories = $formattedSubCategories->sortBy(function ($subCategory) {
            if ($subCategory['sub_category_name'] === 'Spares') {
                return 0;
            } elseif ($subCategory['sub_category_name'] === 'Accessories') {
                return 1;
            }
            return 2;
        })->values();

        if ($orderedSubCategories->isNotEmpty()) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $orderedSubCategories,
                'count' => $orderedSubCategories->count(),
            ], 200);
        } else {
            return response()->json([
                'message' => 'Failed to get data successfully!',
            ], 400);
        }
    }


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
                    'user_id' => $user->id,
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
                    'user_id' => $user->id,
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

    public function orders_user_id(Request $request, $id = null)
    {
        $get_user = Auth::User();

        if ($get_user->role == 'user') {
            $id = $get_user->id;
        } else {
            $request->validate([
                'user_id' => 'required',
            ]);
            $id = $request->input('user_id');
        }

        // Fetch all orders and their associated order items with product image
        $get_user_orders = OrderModel::when($id, function ($query, $id) {
            return $query->where('user_id', $id);
        })
        ->with(['order_items' => function($query) {
            // Eager load product relationship and append the product_image field
            $query->with('product:id,product_code,product_image');
        }])
        ->get();

        // Modify the order items to append the product image directly
        $get_user_orders->each(function($order) {
            $order->order_items->each(function($orderItem) {
                $orderItem->product_image = $orderItem->product->product_image ?? null;
                unset($orderItem->product); // Remove the product object after extracting the image
            });
        });

        if ($get_user_orders->isEmpty()) {
            return response()->json([
                'message' => 'Sorry, no data available!',
            ], 404);
        } else {
            return response()->json([
                'message' => 'Fetched data successfully!',
                'data' => $get_user_orders
            ], 200);
        }
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
        ? response()->json(['Fetch all recods successfully!', 'data' => $create_cart], 200)
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
					't_cart.remarks',
					't_cart.rate',
					't_cart.quantity',
					't_cart.amount',
					// 't_cart.created_at',
					// 't_cart.updated_at',
					// 't_products.basic',
					// 't_products.gst',
					't_products.product_image'
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
					't_products.product_image'
				)
				->get();
        }
        

        return isset($get_items_for_user) && $get_items_for_user->isNotEmpty()
        ? response()->json(['Fetch data successfully!', 'data' => $get_items_for_user, 'record count' => count($get_items_for_user)], 200)
        : response()->json(['Sorry, your cart is empty', 'data' => array(), 'record count' => 0], 200);  
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