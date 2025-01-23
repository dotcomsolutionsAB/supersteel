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

ini_set('memory_limit', '512M'); // Adjust as needed
set_time_limit(300); // Increase timeout to 5 minutes or as needed

class InvoiceControllerZP extends Controller
{
    //
    public function generateorderInvoiceZP($orderId, $is_edited = false, $type = 'quotation', $notification_flag = false)
    {
        // $get_user = Auth::id();

        $order = OrderModel::select('user_id','order_id', 'amount', 'order_date', 'remarks')
                            ->where('id', $orderId)
                            ->first();

        $get_user = $order->user_id;
        
        $user = User::select('name', 'mobile', 'email', 'address_line_1', 'address_line_2', 'gstin','manager_id')
                    ->where('id', $get_user)
                    ->first();

        $manager_id = $user ? $user->manager_id : null;
        
        $order_items = OrderItemsModel::with('product:product_code,print_name')
                                    ->select('product_code', 'product_name', 'rate', 'quantity', 'total', 'remarks')
                                    ->where('order_id', $orderId)
                                    ->get();

        // $mobileNumbers = array_unique(array_merge($adminNumbers, $managerNumbers));
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

        $headerHtml = view('invoice_template_header_zp', ['user' => $user, 'order' => $order])->render();

        $mpdf->WriteHTML($headerHtml);

        $chunkSize = 10;
		$orderItems = collect($order_items)->chunk($chunkSize);

        foreach ($orderItems as $chunk) {
			foreach ($chunk as $index => $item) {
				// Render each item row individually
				$htmlChunk = view('invoice_template_items_zp', compact('item', 'index'))->render();
				$mpdf->WriteHTML($htmlChunk);
			}
			ob_flush();
			flush();
		}

        // Render the footer
		$footerHtml = view('invoice_template_footer_zp', ['order' => $order])->render();
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
                if($mobileNumber != '+917003541353' || true)
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

            if(($user->notifications === 1 && $type == 'order') || $notification_flag)
            {
                $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'User Order Invoice');
            }
            // return $mpdf->Output('invoice.pdf', 'I');
            return $fileUrl;
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
                if($mobileNumber == '+917003541353' || true)
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

            if(($user->notifications === 1 && $type == 'order') || $notification_flag)
            {
                $response = $whatsAppUtility->sendWhatsApp($user->mobile, $templateParams, '', 'User Order Invoice');
            }

            // return $mpdf->Output('invoice.pdf', 'I');
            return $fileUrl;
        }
    }
}
