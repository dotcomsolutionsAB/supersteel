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
        $user_id = $request->input('user_id');  // Assuming the user ID is provided in the request

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
            $query->select('id', 'product_code', 'product_name', 'print_name', 'brand', 'c1', 'c2', 'c3', 'c4', 'c5', 'type', 'machine_part_no', DB::raw("$price_column as price"), 'product_image');
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
		
		// Initialize the product query
		$query = ProductModel::query();

		// Define your distinct values query based on the provided parameters.
		if (empty($request->c1) && empty($request->c2) && empty($request->c3) && empty($request->c4)) {
			// No parameters provided: Fetch distinct c1 from products table
			$distinctValues = $query->distinct()->pluck('c1');
		} elseif (!empty($request->c1) && empty($request->c2) && empty($request->c3) && empty($request->c4)) {
			// Only c1 is provided: Fetch distinct c2 where c1 == $request->c1
			$distinctValues = $query->where('c1', $request->c1)->distinct()->pluck('c2');
		} elseif (!empty($request->c1) && !empty($request->c2) && empty($request->c3) && empty($request->c4)) {
			// c1 and c2 are provided: Fetch distinct c3 where c1 == $request->c1 and c2 == $request->c2
			$distinctValues = $query->where('c1', $request->c1)->where('c2', $request->c2)->distinct()->pluck('c3');
		} elseif (!empty($request->c1) && !empty($request->c2) && !empty($request->c3) && empty($request->c4)) {
			// c1, c2, and c3 are provided: Fetch distinct c4 where c1 == $request->c1, c2 == $request->c2, and c3 == $request->c3
			$distinctValues = $query->where('c1', $request->c1)->where('c2', $request->c2)->where('c3', $request->c3)->distinct()->pluck('c4');
		} else {
			// All c1, c2, c3, and c4 are provided: Fetch distinct c5 where all conditions are met
			$distinctValues = $query->where('c1', $request->c1)
									->where('c2', $request->c2)
									->where('c3', $request->c3)
									->where('c4', $request->c4)
									->distinct()
									->pluck('c5');
		}

		// Fetch all categories with their product count based on the distinct category IDs
		if ($distinctValues->isNotEmpty()) {
			$categories = CategoryModel::whereIn('code', $distinctValues)->get();

			// Format the categories data for a JSON response
			$formattedCategories = $categories->map(function ($category) use ($request) {
				
				$productQuery = ProductModel::query();

				// Always apply the condition for c1, since it's always required
				$productQuery->where('c1', $request->c1);

				// Conditionally apply c2, c3, and c4 based on the request
				if (!empty($request->c2)) {
					$productQuery->where('c2', $request->c2);
				} else {
					$productQuery->where('c2', $category->code); // Default when c2 is not passed
				}

				if (!empty($request->c3)) {
					$productQuery->where('c3', $request->c3);
				} else if (!empty($request->c2)) {
					$productQuery->where('c3', $category->code); // Default when c3 is not passed but c2 is provided
				}

				if (!empty($request->c4)) {
					$productQuery->where('c4', $request->c4);
				} else if (!empty($request->c3)) {
					$productQuery->where('c4', $category->code); // Default when c4 is not passed but c3 is provided
				}

				// Always apply c5 with $category->code when all the previous conditions are met
				if (!empty($request->c1) && !empty($request->c2) && !empty($request->c3) && !empty($request->c4)) {
					$productQuery->where('c5', $category->code);
				}

				// Manually count the number of products for this category
				$productCount = $productQuery->count();

				
				return [
					'category_id' => $category->code,
					'category_name' => $category->product_code,
					'category_image' => $category->category_image,
					'product_count' => $productCount,  // Product count based on AND condition
				];
			});

			return response()->json([
				'message' => 'Fetch data successfully!',
				'data' => $formattedCategories,
				'count' => count($formattedCategories),
			], 200);
		}

		// If no categories were found
		return response()->json([
			'message' => 'Failed to get data!',
		], 404);
	}

	public function sub_category(Request $request)
	{
		// Initialize the product query
		$query = ProductModel::query();

		// Check if c2 is provided, if not return error
		if (empty($request->c2)) {
			return response()->json([
				'message' => 'c2 is required!',
			], 400);
		}

		// Fetch all distinct combinations of c2, c3, c4, c5 where c2 equals the request's c2
		$distinctValues = $query->where('c2', $request->c2)
								->distinct()
								->get(['c2', 'c3', 'c4', 'c5']);

		// If no records were found
		if ($distinctValues->isEmpty()) {
			return response()->json([
				'message' => 'No combinations found for the given c2!',
			], 404);
		}

		// Format the distinct combinations to include the required hierarchy, name, and product count
		$formattedSubCategories = $distinctValues->map(function ($row) use ($request) {

			// Determine the last non-null value to pull the name from CategoryModel
			$lastCategoryCode = $row->c5 ?? $row->c4 ?? $row->c3;
			$category = CategoryModel::where('code', $lastCategoryCode)->first();

			// Build the hierarchy string
			$hierarchy = [];
			if ($row->c2) $hierarchy[] = CategoryModel::where('code', $row->c2)->value('product_code');
			if ($row->c3) $hierarchy[] = CategoryModel::where('code', $row->c3)->value('product_code');
			if ($row->c4) $hierarchy[] = CategoryModel::where('code', $row->c4)->value('product_code');
			if ($row->c5) $hierarchy[] = CategoryModel::where('code', $row->c5)->value('product_code');

			// Initialize product query for counting
			$productQuery = ProductModel::query();

			// Always apply the condition for c2 since it's provided
			$productQuery->where('c2', $row->c2);

			// Conditionally apply c3, c4, and c5 based on the non-null values in the current row
			if (!empty($row->c3)) {
				$productQuery->where('c3', $row->c3);
			}
			if (!empty($row->c4)) {
				$productQuery->where('c4', $row->c4);
			}
			if (!empty($row->c5)) {
				$productQuery->where('c5', $row->c5);
			}

			// Manually count the number of products for this sub-category combination
			$productCount = $productQuery->count();

			return [
				'name' => $category ? $category->product_code : 'Unknown', // Pull name of the last category
				'hierarchy' => implode(' > ', array_filter($hierarchy)), // Join the hierarchy names
				'c2' => $row->c2,
				'c3' => $row->c3,
				'c4' => $row->c4,
				'c5' => $row->c5,
				'product_count' => $productCount, // Product count based on AND condition
			];
		});

		return response()->json([
			'message' => 'Sub-category combinations fetched successfully!',
			'data' => $formattedSubCategories,
			'count' => count($formattedSubCategories),
		], 200);
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