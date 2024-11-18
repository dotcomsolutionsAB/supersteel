<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;        
use App\Models\OrderModel;    
use App\Models\OrderItemsModel;
use App\Models\ProductModel;
use Mpdf\Mpdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Utils\sendWhatsAppUtility;
use Carbon\Carbon;
use DB;

class InvoiceController extends Controller
{
    public function generateInvoice($orderId)
    {
        // $get_user = Auth::id();

        $order = OrderModel::select('user_id','order_id', 'amount', 'order_date', 'remarks')
                            ->where('id', $orderId)
                            ->first();

        $get_user = $order->user_id;
        
        $user = User::select('name', 'mobile', 'email', 'address_line_1', 'address_line_2', 'gstin')
                    ->where('id', $get_user)
                    ->first();
        
        

        $order_items = OrderItemsModel::with('product:product_code,print_name')
                                    ->select('product_code', 'product_name', 'rate', 'quantity', 'total', 'remarks')
                                    ->where('order_id', $orderId)
                                    ->get();

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

        $html = view('invoice_template', $data)->render();

        $mpdf = new Mpdf();
        $mpdf->WriteHTML($html);

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

        $templateParams = [
            'name' => 'ace_new_order_user', // Replace with your WhatsApp template name
            'language' => ['code' => 'en'],
            'components' => [
                [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => 'document',
                            'document' => [
                                'link' =>  $fileUrl, // Replace with the actual URL to the PDF document
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
        
        // Directly create an instance of SendWhatsAppUtility
        $whatsAppUtility = new sendWhatsAppUtility();
        
        $response = $whatsAppUtility->sendWhatsApp('+918961043773', $templateParams, '', 'User Order Invoice');

        $templateParams = [
            'name' => 'ace_new_order_admin', // Replace with your WhatsApp template name
            'language' => ['code' => 'en'],
            'components' => [
                [
                    'type' => 'header',
                    'parameters' => [
                        [
                            'type' => 'document',
                            'document' => [
                                'link' =>  $fileUrl, // Replace with the actual URL to the PDF document
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

        $response = $whatsAppUtility->sendWhatsApp('+917003541353', $templateParams, '', 'Admin Order Invoice');

        $response = $whatsAppUtility->sendWhatsApp('+919908570858', $templateParams, '', 'Admin Order Invoice');
        

        // // Assuming additional functionality such as WhatsApp integration etc.
        // return $mpdf->Output('invoice.pdf', 'I');
        return $fileUrl;
    }

    public function price_spares($code)
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
            case 'e':
                $price_column = 'price_e';
            // Add more cases as needed
            default:
            // In case of no matching price type, select all price columns
                $price_column = 'price_a';
                break;
        }

        $get_product_details = ProductModel::select('product_name', 'product_code', 'product_image')
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
            <h1 style="text-align: center; padding: 20px;">Sorry, no spare available</h1>';
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
              
        // Load the Blade view and pass the data
        $html = view('spare_pricelist', compact('get_product_details', 'get_record'))->render();

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
}
