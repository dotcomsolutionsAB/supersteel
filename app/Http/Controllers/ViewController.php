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

class ViewController extends Controller
{
    //
    public function product()
    {
        // $get_product_details = ProductModel::select('SKU','product_code','product_name','category','sub_category','product_image','basic','gst','mark_up')->get();
        $get_product_details = ProductModel::select('SKU','product_code','product_name','category','sub_category','product_image','basic','gst')->get();
        

        if (isset($get_product_details)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_product_details
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function lng_product($lang = 'eng')
    {
        $get_product_details = ProductModel::select('SKU','product_code','product_name', 'name_in_hindi','name_in_telugu','category','sub_category','product_image','basic','gst')->get();
        
        $processed_prd_rec = $get_product_details->map(function($prd_rec) use ($lang)
        {
            $product_name = $prd_rec->product_name;

            if($lang === 'hin' && !empty($prd_rec->name_in_hindi))
            {
                $product_name = $prd_rec->name_in_hindi;
            }

            elseif ($lang === 'tlg' && !empty($prd_rec->name_in_telugu)) {
                $product_name = $prd_rec->name_in_telugu;
            }

            return [
                'SKU' => $prd_rec->SKU,
                'SKU' => $prd_rec->product_code,
                'product_name' => $product_name,
                'category' => $prd_rec->category,
                'sub_category' => $prd_rec->sub_category,
                'product_image' => $prd_rec->product_image,
                'basic' => $prd_rec->basic,
                'gst' => $prd_rec->gst,
            ];
        });


        if (isset($get_product_details)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $processed_prd_rec
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function get_product(Request $request)
    {
        // Retrieve offset and limit from the request with default values
        $offset = $request->input('offset', 0); // Default to 0 if not provided
        $limit = $request->input('limit', 10);  // Default to 10 if not provided
        $user_id = $request->input('user_id');  // Assuming the user ID is provided in the request

        // Ensure the offset and limit are integers and non-negative
        $offset = max(0, (int) $offset);
        $limit = max(1, (int) $limit);

        // Retrieve filter parameters if provided
        $search = $request->input('search', null);
        $category = $request->input('category', null);
        $subCategory = $request->input('sub_category', null);

        // Build the query for products
        $query = ProductModel::select('SKU', 'product_code', 'product_name', 'category', 'sub_category', 'product_image', 'basic', 'gst');

        // Apply search filter if provided
        if ($search) {
            $query->where('product_name', 'like', "%{$search}%");
        }

        // Apply category filter if provided
        if ($category) {
            $query->where('category', $category);
        }

        // Apply sub-category filter if provided
        if ($subCategory) {
            $query->where('sub_category', $subCategory);
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
                if ($cart_item) {
                    $product->in_cart = true;
                    $product->cart_quantity = $cart_item->quantity;
                    $product->cart_type = $cart_item->type;
                } else {
                    // If the product is not in the cart
                    $product->in_cart = false;
                    $product->cart_quantity = null;  // or 0, depending on your preference
                    $product->cart_type = null;
                }
            }

            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_products
            ], 201);

        } else {
            return response()->json([
                'message' => 'Failed to fetch data!',
            ], 400);
        }
    }

    public function lng_get_product(Request $request, $lang = 'eng')
    {
        // Retrieve input parameters with defaults
        $offset = max(0, (int) $request->input('offset', 0));
        $limit = max(1, (int) $request->input('limit', 10));
        $user_id = $request->input('user_id');
        $search = $request->input('search', null);
        $category = $request->input('category', null);
        $subCategory = $request->input('sub_category', null);

        // Build the query for products
        $query = ProductModel::select(
            'SKU', 'product_code', 'product_name', 'name_in_hindi', 'name_in_telugu', 'category', 'sub_category', 'product_image', 'basic', 'gst'
        );

        // Apply filters
        if ($search) {
            $query->where('product_name', 'like', "%{$search}%");
        }
        if ($category) {
            $query->where('category', $category);
        }
        if ($subCategory) {
            $query->where('sub_category', $subCategory);
        }

        // Apply pagination and get products
        $get_products = $query->skip($offset)->take($limit)->get();

        // Process products for language and cart details
        $processed_prd_lang_rec = $get_products->map(function ($prd_rec) use ($lang, $user_id) {
            // Set product name based on the selected language
            $product_name = $prd_rec->product_name;
            if ($lang === 'hin' && !empty($prd_rec->name_in_hindi)) {
                $product_name = $prd_rec->name_in_hindi;
            } elseif ($lang === 'tlg' && !empty($prd_rec->name_in_telugu)) {
                $product_name = $prd_rec->name_in_telugu;
            }

            // Check if the product is in the user's cart
            $cart_item = CartModel::where('user_id', $user_id)
                ->where('product_code', $prd_rec->product_code)
                ->first();

            // Return processed product data
            return [
                'SKU' => $prd_rec->SKU,
                'product_code' => $prd_rec->product_code,
                'product_name' => $product_name,
                'category' => $prd_rec->category,
                'sub_category' => $prd_rec->sub_category,
                'product_image' => $prd_rec->product_image,
                'basic' => $prd_rec->basic,
                'gst' => $prd_rec->gst,
                'in_cart' => $cart_item ? true : false,
                'cart_quantity' => $cart_item->quantity ?? null,
                'cart_type' => $cart_item->type ?? null,
            ];
        });

        // Return response based on the result
        return $processed_prd_lang_rec->isEmpty()
        ? response()->json(['Failed to fetch data!'], 400)
        : response()->json(['message' => 'Fetch data successfully!',
                'data' => $processed_prd_lang_rec,
                'count' => count($processed_prd_lang_rec)], 201);
    }

    public function categories()
    {
        // Fetch all categories with their product count
        $categories = CategoryModel::withCount('get_products')->get();

        // Format the categories data for a JSON response
        $formattedCategories = $categories->map(function ($category) {
            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_image' => $category->image,
                'products_count' => $category->get_products_count,
            ];
        });

        if (isset($formattedCategories)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $formattedCategories,
                'count' => count($formattedCategories),
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function sub_categories($category = null)
    {
        // Convert the string of category IDs to an array, e.g., '1,2' -> [1, 2]
        $categoryIds = $category ? explode(',', $category) : [];

        // // Fetch subcategories filtered by category_id if provided
        // $sub_categories = SubCategoryModel::withCount('products')
        // ->when($category, function ($query, $category) {
        //     // Filter subcategories by the category_id if a category is provided
        //     return $query->where('category_id', $category);
        // })->get();

        // Fetch subcategories filtered by multiple category_ids if provided
        $sub_categories = SubCategoryModel::withCount('products')
        ->when(!empty($categoryIds), function ($query) use ($categoryIds) {
            // Filter subcategories by multiple category_ids using whereIn
            return $query->whereIn('category_id', $categoryIds);
        })->get();

        // Format the categories data for a JSON response
        $formattedSubCategories = $sub_categories->map(function ($sub_category) {
            return [
                'sub_category_name' => $sub_category->name,
                'sub_category_image' => $sub_category->image,
                'sub_products_count' => $sub_category->products_count,
            ];
        });
        
        if (isset($formattedSubCategories)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $formattedSubCategories,
                'count' => count($formattedSubCategories),
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function lng_sub_categories($category = null, $lang = 'eng')
    {
        $categoryIds = $category ? explode(',', $category) : [];

        // Fetch subcategories filtered by multiple category_ids if provided
        $sub_categories = SubCategoryModel::withCount('products')
        ->when(!empty($categoryIds), function ($query) use ($categoryIds) 
        {
            // Filter subcategories by multiple category_ids using whereIn
            return $query->whereIn('category_id', $categoryIds);
        })
        ->get();

        // Format the subcategories data for a JSON response
        $formattedSubCategories = $sub_categories->map(function ($sub_category) use ($lang) 
        {
            // Set the sub-category name based on the selected language
            $sub_category_name = $sub_category->name; // Default to English

            if ($lang === 'hin' && !empty($sub_category->name_in_hindi)) {
                $sub_category_name = $sub_category->name_in_hindi;
            } elseif ($lang === 'tlg' && !empty($sub_category->name_in_telugu)) {
                $sub_category_name = $sub_category->name_in_telugu;
            }

            return [
                'sub_category_name' => $sub_category_name,
                'sub_category_image' => $sub_category->image,
                'sub_products_count' => $sub_category->products_count,
            ];
        });
        
        return $formattedSubCategories->isEmpty()
        ? response()->json(['Failed get data successfully!'], 400)
        : response()->json(['message' => 'Fetch data successfully!',
                'data' => $formattedSubCategories,
                'count' => count($formattedSubCategories)], 201);
    }

    public function lng_categories($lang = 'eng')
    {
        // Fetch all categories with their product count
        $categories = CategoryModel::withCount('get_products')->get();

        // Format the categories data for a JSON response
        $formattedCategories = $categories->map(function ($category) use ($lang) {

            $category_name = $category->name;

            if($lang === 'hin' && !empty($category->name_in_hindi))
            {
                $category_name = $category->name_in_hindi;
            }

            elseif ($lang === 'tlg' && !empty($category->name_in_telugu)) 
            {
                $category_name = $category->name_in_telugu;
            }
            return [
                'category_id' => $category->id,
                'category_name' => $category_name,
                'category_image' => $category->image,
                'products_count' => $category->get_products_count,
            ];
        });
        // Check if the categories are set and return response
        return $formattedCategories->isEmpty()
        ? response()->json(['Failed get data successfully!'], 400)
        : response()->json(['message' => 'Fetch data successfully!',
                'data' => $formattedCategories,
                'count' => count($formattedCategories)], 201);
    }

    public function user($lang = 'eng')
    {
        $get_user_details = User::select('id','name', 'name_in_hindi', 'name_in_telugu', 'email','mobile','role','address_line_1','address_line_2','city','pincode','gstin','state','country', 'verified')->get();

        $processed_rec_user = $get_user_details->map(function ($record) use ($lang)
        {
            $name = $record->name;

            if($lang == 'hin' && !empty($record->name_in_hindi))
            {
                $name = $record->name_in_hindi;
            }
            elseif ($lang == 'tlg' && !empty($record->name_in_telugu)) 
            {
                $name = $record->name_in_telugu;
            }

                return [
                    'id' => $record->id,
                    'name' => $name,
                    'email' => $record->email,
                    'mobile' => $record->mobile,
                    'role' => ucfirst($record->role),
                    'address' => implode(', ', array_filter([$record->address_line_1, $record->address_line_2, $record->city, $record->state, $record->pincode, $record->country])),
                    'gstin' => $record->gstin,
                    'verified' => $record->verified,
                ];  
        }) ;
        
        

        // if (isset($get_user_details)) {
        //     return response()->json([
        //         'message' => 'Fetch data successfully!',
        //         'data' => $get_user_details
        //     ], 201);
        // }

        // else {
        //     return response()->json([
        //         'message' => 'Failed get data successfully!',
        //     ], 400);
        // }    

        return $processed_rec_user->isEmpty()
        ? response()->json(['Failed get data successfully!'], 400)
        : response()->json(['Fetch data successfully!', 'data' => $processed_rec_user], 201);
    }

    public function find_user($search = null)
    {   
        if ($search == null) {
            $get_user_details = User::select('id','name','email','mobile','role','address_line_1','address_line_2','city','pincode','gstin','state','country')
                                ->get();     
        }
        else {
            $get_user_details = User::select('id','name','email','mobile','role','address_line_1','address_line_2','city','pincode','gstin','state','country')
                                ->where('name', $search)
                                ->orWhere('mobile', $search)
                                ->get();     
        }

        if (isset($get_user_details) && (!$get_user_details->isEmpty())) {
            return response()->json([
                'message' => 'Fetch record successfully!',
                'data' => $get_user_details
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function user_details()
    {
        $get_user_id = Auth::id();
        
        $get_user_details = User::select('id','name','email','mobile','address_line_1','address_line_2','city','pincode','gstin','state','country')->where('id', $get_user_id)->get();
        

        if (isset($get_user_details)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_user_details
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function orders()
    {
        $get_all_orders = OrderModel::with('user')->get();
        

        if (isset($get_all_orders)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_all_orders
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function orders_user_id($id = null)
    {
        // Fetch all records if $id is null, otherwise filter by user_id
        $get_user_orders = OrderModel::when($id, function($query, $id)
        {
            // If $id is not null, filter by user_id
            return $query->where('user_id', $id);
            
        })->get();   

        if($get_user_orders->isEmpty()) {
            return response()->json([
                'message' => 'Sorry, no data available!',
            ], 400);
        }

        else {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_user_orders
            ], 201);
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
        // $get_items_for_orders = OrderItemsModel::where('order_id', $id)
        // ->join()
        // ->get();
        

        if (isset($get_items_for_orders)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_items_for_orders
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function cart()
    {
        // Retrieve all records with their associated user and product data
        $get_all_cart_records = CartModel::with(['get_users', 'get_products'])->get();
        

        // Transform the data if needed
        $formattedData = $get_all_cart_records->map(function ($item) {
			
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
        if (isset($formattedData)) {
            return response()->json([
                'message' => 'Fetch all recods successfully!',
                'data' => $formattedData
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed fetch records successfully!',
            ], 400);
        }    
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
					't_cart.type',
					't_cart.created_at',
					't_cart.updated_at',
					't_products.basic',
					't_products.gst',
					't_products.product_image'
				)
				->get();

            $cart_data_count = count($get_items_for_user);
        }

        else {
            //$get_items_for_user = CartModel::where('user_id', $get_user->id)->get();
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
					't_cart.type',
					't_cart.created_at',
					't_cart.updated_at',
					't_products.basic',
					't_products.gst',
					't_products.product_image'
				)
				->get();


            $cart_data_count = count($get_items_for_user);
        }
        

        if (isset($get_items_for_user)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_items_for_user,
                'record count' => $cart_data_count
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function counter()
    {
        $get_counter_records = CounterModel::all();
        
        if (isset($get_counter_records)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_counter_records
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
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
        
        if (isset($get_dashboard_records)) {
            return response()->json([
                'message' => 'Fetch records successfully!',
                'data' => $get_dashboard_records
            ], 201);
        }

        else {
            return response()->json([
                'message' => 'Sorry, failed get records',
            ], 400);
        }    
    }

    public function return_order($orderId)
    {
        // \DB::enableQueryLog();
        $get_order_details = OrderModel::with('order_items')
                                        ->where('id', $orderId)
                                        ->get();
                                        // dd(\DB::getQueryLog());


        if ($get_order_details) 
        {
            if ($get_order_details[0]->type == 'Basic') {
                $get_invoice_id = CounterModel::where('name', 'invoice_basic')
                                                ->get();

                $return_invoice_id = $get_invoice_id[0]->prefix.$get_invoice_id[0]->counter.$get_invoice_id[0]->postfix;
            }
            else {
                $get_invoice_id = CounterModel::where('name', 'invoice_basic')
                ->get();

                $return_invoice_id = $get_invoice_id[0]->prefix.$get_invoice_id[0]->counter.$get_invoice_id[0]->postfix;
            }

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
        

        if (empty($formatted_order_record)) 
        {
            return response()->json(['message' => 'Failed to get order records!'], 400);
        } 
        else 
        {
            return response()->json([
                'message' => 'Fetch records successfully!',
                'data' => $formatted_order_record
            ], 200);
        }
    }

    // return blade file
    
    public function login_view()
    {
        return view('login');
    }

    public function user_view()
    {
        return view('view_user');
    }
}