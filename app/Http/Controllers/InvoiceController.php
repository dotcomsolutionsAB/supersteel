<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;        
use App\Models\OrderModel;    
use App\Models\OrderItemsModel;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use Mpdf\Mpdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Utils\sendWhatsAppUtility;
use Carbon\Carbon;
use DB;

ini_set('memory_limit', '512M'); // Adjust as needed
set_time_limit(300); // Increase timeout to 5 minutes or as needed

class InvoiceController extends Controller
{

    public function generateInvoice($orderId, $is_edited = false)
    {
        // $get_user = Auth::id();

        $order = OrderModel::select('user_id','order_id', 'amount', 'order_date', 'remarks')
                            ->where('id', $orderId)
                            ->first();

        $get_user = $order->user_id;
        // $manager_id = $order->manager_id;
        // Fetch the user using the user_id
        $user = User::find($get_user);

        // Retrieve the manager_id from the user
        $manager_id = $user ? $user->manager_id : null;
        
        $user = User::select('name', 'mobile', 'email', 'address_line_1', 'address_line_2', 'gstin','transport', 'billing_style')
                    ->where('id', $get_user)
                    ->first();
        
        $order_items = OrderItemsModel::with('product:product_code,print_name')
                                    ->select('product_code', 'product_name', 'rate', 'quantity', 'total', 'remarks')
                                    ->where('order_id', $orderId)
                                    ->get();
        $adminNumbers = User::where('role', 'admin')->pluck('mobile')->toArray();
        $managerNumbers = User::where('id', $manager_id)->pluck('mobile')->toArray();

        $mobileNumbers = array_unique(array_merge($adminNumbers, $managerNumbers));

        foreach($order_items as $item)
        {
            $filename = $item->product_code;
            $productImagePathPdf = "/storage/uploads/products_pdf/{$filename}.jpg";

            if (file_exists(public_path($productImagePathPdf))) {
                $item->product_image = $productImagePathPdf;
            }
    
            else {
                $get_product_image = ProductModel::select('product_image')->where('product_code', $item->product_code)->first();

                $item->product_image = $get_product_image->product_image;
            }
        }                           

        if (!$user || !$order || $order_items->isEmpty()) {
            return response()->json(['error' => 'Sorry, required data are not available!'], 500);
        }

        $sanitizedOrderId = preg_replace('/[^A-Za-z0-9]+/', '-', trim($order->order_id));
        $sanitizedOrderId = trim($sanitizedOrderId, '-');

        $data = [
            'user' => $user,
            'order' => $order,
            'order_items' => $order_items,
        ];

        // $html = view('invoice_template', $data)->render();

        $mpdf = new Mpdf();

        $headerHtml = view('invoice_template_header', ['user' => $user, 'order' => $order])->render();

        $mpdf->WriteHTML($headerHtml);

        $chunkSize = 10;
		$orderItems = collect($order_items)->chunk($chunkSize);

        foreach ($orderItems as $chunk) {
			foreach ($chunk as $index => $item) {
				// Render each item row individually
				$htmlChunk = view('invoice_template_items', compact('item', 'index'))->render();
				$mpdf->WriteHTML($htmlChunk);
			}
			ob_flush();
			flush();
		}

        // Render the footer
		$footerHtml = view('invoice_template_footer', ['order' => $order])->render();
		$mpdf->WriteHTML($footerHtml);

        // Output the PDF
        $publicPath = 'uploads/invoices/';
        $fileName = 'invoice_' . $sanitizedOrderId . '.pdf';
        $filePath = storage_path('app/public/' . $publicPath . $fileName);

        if (!File::isDirectory($storage_path = storage_path('app/public/' . $publicPath))) {
            File::makeDirectory($storage_path, 0755, true);
        }

        $mpdf->Output($filePath, 'F');

        $fileUrl = asset('storage/' . $publicPath . $fileName);

        $update_order = OrderModel::where('id', $orderId)
        ->update([
            'order_invoice' => $fileUrl,
        ]);

        // Directly create an instance of SendWhatsAppUtility
        $whatsAppUtility = new sendWhatsAppUtility();

        if(!$is_edited)
        {
            $fileUrlWithTimestamp = $fileUrl . '?t=' . time();
            $templateParams = [
                'name' => 'ss_new_order_admin', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $user->name,
                            ],
                            [
                                'type' => 'text',
                                'text' =>  substr($user->mobile, -10),
                            ],
                            [
                                'type' => 'text',
                                'text' => $order->order_id,
                            ],
                            [
                                'type' => 'text',
                                'text' => Carbon::now()->format('d-m-Y'),
                            ],
                            [
                                'type' => 'text',
                                'text' => $order->amount,
                            ],
                        ],
                    ]
                ],
            ];

            foreach ($mobileNumbers as $mobileNumber) 
            {
                if($mobileNumber != '+917003541353')
                {
                    // Send message for each number
                    $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Admin Order Invoice');

                    // Check if the response has an error or was successful
                    if (isset($responseArray['error'])) 
                    {
                        echo "Failed to send order to Whatsapp!";
                    }
                }
            }

            $templateParams = [
                'name' => 'ss_new_order_user', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
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
                                'text' => Carbon::now()->format('d-m-Y'),
                            ],
                            [
                                'type' => 'text',
                                'text' => $order->amount,
                            ],
                        ],
                    ]
                ],
            ];
            // if($user->notifications === 1)
            // {
            //     $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'User Order Invoice');
            // }
            
        }else{
            $fileUrlWithTimestamp = $fileUrl . '?t=' . time();
            $templateParams = [
                'name' => 'ss_edit_order_admin', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $user->name,
                            ],
                            [
                                'type' => 'text',
                                'text' =>  substr($user->mobile, -10),
                            ],
                            [
                                'type' => 'text',
                                'text' => $order->order_id,
                            ],
                            [
                                'type' => 'text',
                                'text' => Carbon::parse($order->order_date)->format('d-m-Y'),
                            ],
                            [
                                'type' => 'text',
                                'text' => $order->amount,
                            ],
                        ],
                    ]
                ],
            ];

            foreach ($mobileNumbers as $mobileNumber) 
            {
                if($mobileNumber != '+917003541353')
                {
                    // Send message for each number
                    $response = $whatsAppUtility->sendWhatsApp($mobileNumber, $templateParams, '', 'Admin Order Invoice');

                    // Check if the response has an error or was successful
                    if (isset($responseArray['error'])) 
                    {
                        echo "Failed to send order to Whatsapp!";
                    }
                }
            }

            $templateParams = [
                'name' => 'ss_edit_order_user', // Replace with your WhatsApp template name
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'header',
                        'parameters' => [
                            [
                                'type' => 'document',
                                'document' => [
                                    'link' =>  $fileUrlWithTimestamp, // Replace with the actual URL to the PDF document
                                    'filename' => $sanitizedOrderId.'.pdf' // Optional: Set a custom file name for the PDF document
                                ]
                            ]
                        ]
                    ],[
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
                            [
                                'type' => 'text',
                                'text' => $order->amount,
                            ],
                        ],
                    ]
                ],
            ];
            // if($user->notifications === 1)
            // {
            //     $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'User Order Invoice');
            // }
        }
        

        // // Assuming additional functionality such as WhatsApp integration etc.
        // return $mpdf->Output('invoice.pdf', 'I');
        return $fileUrl;
    }

    public function price_spares(Request $request, $code)
    {
        // initialize the query
        $query = ProductModel::query();

        $get_user = Auth::User();

        if($get_user->role == 'user') {
            $get_user = Auth::User();

            $user_price = $get_user->price_type;
            
            $user_name = $get_user->name;
        }

        else{
            $request->validate([
                'id' => 'required|integer'
            ]);  

            $id = $request->input('id');

            $get_user_price = User::select('price_type', 'name')->where('id', $id)->first();

            $user_price = $get_user_price->price_type;

            $user_name = $get_user_price->name;
        }

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
            case 'i':
                $price_column = 'price_i';
                break;
            default:
                $price_column = 'price_a';
                break;
        }

        $get_product_details = ProductModel::select('product_name', 'print_name', 'product_code', 'product_image')
                                            ->where('product_code', $code)
                                            ->first();


        $get_record = $query->select('product_code', 'print_name', 'brand', DB::raw("$price_column as price"), 'product_image')
              ->where('machine_part_no', $code)
              ->skip(1) // Skip the first record
              ->take(PHP_INT_MAX) // Take all remaining records
              ->get();

        if (count($get_record) == 0)
        {
            $mpdf = new Mpdf();
            $html = '
            <div class="title-box" style="background-color: brown; color: white; text-align: center; padding: 20px; font-size: 24px; font-weight: bold; border-radius: 8px 8px 0 0;">
                ' . $get_product_details->product_name . ' - ' . $get_product_details->product_code . '
            </div>
            <h1 style="text-align: center; padding: 20px;">Sorry, no spare available</h1>
            ';
            
            $mpdf->writeHTML($html);

            // Prepare the file path and name
            $publicPath = 'uploads/spare/';
            $sanitizedProductName = str_replace(' ', '_', $get_product_details->product_name); // Sanitize product name to avoid spaces in file name
            $fileName = $get_product_details->product_code . '_' . $sanitizedProductName . '.pdf';
            $filePath = storage_path('app/public/' . $publicPath . $fileName);

            // Create directory if it doesn't exist
            if (!File::isDirectory($storage_path = storage_path('app/public/' . $publicPath))) {
                File::makeDirectory($storage_path, 0755, true);
            }

            // Save the blank PDF to the file system
            $mpdf->Output($filePath, 'F');

            // Generate the file URL
            $fileUrl = asset('storage/' . $publicPath . $fileName);

            return $fileUrl; // Return the file URL for the blank PDF
        }
        
        if($get_user->role == 'user') {
            // Load the Blade view and pass the data
            $html = view('spare_pricelist_user', compact('get_product_details', 'get_record', 'user_name'))->render();
        }else{
            $html = view('spare_pricelist', compact('get_product_details', 'get_record', 'user_name'))->render();
        }

        // create the instance of Mpdf
        $mpdf = new Mpdf();

        // write the html content
        $mpdf->writeHTML($html);

        $publicPath = 'uploads/spare/';
        $sanitizedProductName =  $get_product_details->product_name;
        $fileName = $get_product_details->product_code. '_' . $sanitizedProductName . '.pdf';
        $filePath = storage_path('app/public/' . $publicPath . $fileName);

        if (!File::isDirectory($storage_path = storage_path('app/public/' . $publicPath))) {
            File::makeDirectory($storage_path, 0755, true);
        }

        $mpdf->Output($filePath, 'F');

        $fileUrl = asset('storage/' . $publicPath . $fileName);

        return $fileUrl;

    }

    public function price_list(Request $request)
    {
        // Accept parameters
        $category = $request->input('category');
        $search_text = $request->input('search_text');

        // Fetch the category model using the provided ID
        $categoryArr = CategoryModel::find($category);
        $category_id = '';

        if ($categoryArr) {
            // Dynamically determine the category_id based on the logic
            $category_id = $categoryArr->category_id;
            // Proceed with $category_id
        } 

        // Get the authenticated user
        $get_user = Auth::User();

        // Determine price type and user name based on role
        if ($get_user->role == 'user') {
            $user_price = $get_user->price_type;
            $user_name = $get_user->name;
        } else {
            $request->validate([
                'id' => 'required|integer'
            ]);

            $id = $request->input('id');
            $get_user_price = User::select('price_type', 'name')->where('id', $id)->first();

            $user_price = $get_user_price->price_type;
            $user_name = $get_user_price->name;
        }

        // Map price type to the corresponding column
        $price_column = '';
        switch ($user_price) {
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
            default:
                $price_column = 'price_a';
                break;
        }

        

        // Build the query
        $query = ProductModel::select('product_name','print_name', 'product_code', 'brand', DB::raw("$price_column as price"), 'product_image')
        ->where('product_image', '!=', '');


        if ($category) {
            $query->where('category', $category);
        }

        if ($search_text) {
            $query->where(function ($q) use ($search_text) {
                $q->where('product_name', 'LIKE', "%$search_text%")
                ->orWhere('product_code', 'LIKE', "%$search_text%");
            });
        }

        // Limit the results to 200
        $get_product_details = $query->take(200)->get();
		//dd($get_product_details[0]->product_image);

        if ($get_product_details->isEmpty()) {
            return response()->json(['message' => 'No products found.'], 200);
        }

        if($get_user->role == 'user') {
            // Generate HTML content for the PDF
            $html = view('price_list_user', compact('get_product_details', 'user_name'))->render();
        }else{
            // Generate HTML content for the PDF
            $html = view('price_list', compact('get_product_details', 'user_name'))->render();
        }

        // Create an instance of Mpdf
        $mpdf = new Mpdf();

        // Write the HTML content to the PDF
        $mpdf->writeHTML($html);

        // Define the file path and name
        $publicPath = 'uploads/price_list/';
        $timestamp = now()->format('Ymd_His'); // Generate a timestamp
        $fileName = 'price_list_' . $timestamp . '.pdf'; // Append timestamp to the file name
        $filePath = storage_path('app/public/' . $publicPath . $fileName);

        // Create the directory if it doesn't exist
        if (!File::isDirectory($storage_path = storage_path('app/public/' . $publicPath))) {
            File::makeDirectory($storage_path, 0755, true);
        }

        // Save the PDF to the file system
        $mpdf->Output($filePath, 'F');

        // Generate the file URL
        $fileUrl = asset('storage/' . $publicPath . $fileName);

        return $fileUrl;
    }


}
