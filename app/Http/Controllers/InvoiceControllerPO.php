<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;        
use App\Models\OrderModel;    
use App\Models\OrderItemsModel;
use App\Models\ProductModel;
use Mpdf\Mpdf;
use Mpdf\Barcode\Barcode;
use Mpdf\Image\ImageProcessor;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Utils\sendWhatsAppUtility;
use Carbon\Carbon;
use DB;

ini_set('memory_limit', '512M'); // Adjust as needed
set_time_limit(300); // Increase timeout to 5 minutes or as needed

class InvoiceControllerPO extends Controller
{
    //
    public function generateorderInvoicePO($orderId, $is_edited = false, $type = 'purchase_order', $notification_flag = false)
    {
        // $get_user = Auth::id();

        $order = OrderModel::select('user_id','order_id','type','created_by', 'amount', 'order_date', 'remarks')
                            ->where('id', $orderId)
                            ->first();

        $get_user = $order->user_id;
        
        $user = User::select('name', 'mobile', 'email', 'address_line_1', 'address_line_2', 'gstin','manager_id')
                    ->where('id', $get_user)
                    ->first();

        $created_by = $order->created_by;
        $created_by_user = User::find($created_by);

        $manager_id = $user ? $user->manager_id : null;
        
        $order_items = OrderItemsModel::with('product:product_code,print_name')
                                    ->select('product_code', 'product_name', 'rate', 'quantity', 'total', 'remarks')
                                    ->where('order_id', $orderId)
                                    ->get();

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

        $headerHtml = view('invoice_template_header_po', ['user' => $user, 'order' => $order])->render();

        $mpdf->WriteHTML($headerHtml);

        $chunkSize = 10;
		$orderItems = collect($order_items)->chunk($chunkSize);

        foreach ($orderItems as $chunk) {
			foreach ($chunk as $index => $item) {
				// Render each item row individually
				$htmlChunk = view('invoice_template_items_po', compact('item', 'index'))->render();
				$mpdf->WriteHTML($htmlChunk);
			}
			ob_flush();
			flush();
		}

        // Render the footer
		$footerHtml = view('invoice_template_footer_po', ['order' => $order])->render();
		$mpdf->WriteHTML($footerHtml);


        foreach ($order_items as $item) {
            // Set barcode page dimensions (50mm x 25mm)
            $mpdf->AddPageByArray([
                'margin-left' => 2,
                'margin-right' => 2,
                'margin-top' => 2,
                'margin-bottom' => 2,
                'orientation' => 'P', // Portrait
                'sheet-size' => [50, 50], // Width x Height in mm
            ]);

            // Super Steel Logo (Top Left) & Qty (Top Right)
            $headerHtml = '<div style="display:flex; justify-content:space-between; align-items:center;">
                                <img src="'.public_path('/storage/uploads/super_steel_logo.png').'" width="40" height="20" />
                                <span style="font-size:8px; font-weight:bold;">Qty: </span>
                        </div>';

            // Generate Barcode using Code 39
            $barcodeHtml = '<div style="text-align:center;">
                                <barcode code="'.$item->product_code.'" type="C39" size="0.9" height="1.0"/>
                                <div style="font-size:7px;">' . $item->product_code . '</div>
                            </div>';

            // Item & Model Details (Bottom)
            $itemDetailsHtml = '<div style="text-align:center; font-size:7px;">
                                    <b>Item:</b> '.$item->product->print_name.'<br>
                                    <b>Model:</b> '.$item->product->product_name.'
                                </div>';

            // Wrap in a Container (50mm x 50mm)
            $barcodeBlock = '<div style="width:50mm; height:50mm; text-align:left;">
                                '.$headerHtml.'
                                '.$barcodeHtml.'
                                '.$itemDetailsHtml.'
                            </div>';

            // Write the barcode block to the new page
            $mpdf->WriteHTML($barcodeBlock);
        }

        // Output the PDF
        $publicPath = 'uploads/purchase_order/';
        $fileName = 'po_' . $sanitizedOrderId . '.pdf';
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
                'name' => 'ss_new_order_admin_2', // Replace with your WhatsApp template name
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
                                'text' => '*'.strtoupper($order->type).'*',
                            ],
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
                            [
                                'type' => 'text',
                                'text' => $created_by_user->name,
                            ],
                        ],
                    ]
                ],
            ];
            
            $response = $whatsAppUtility->sendWhatsApp('918961043773', $templateParams, '', 'Admin Order Invoice');
            // $response = $whatsAppUtility->sendWhatsApp('919908570858', $templateParams, '', 'Admin Order Invoice');
            // $response = $whatsAppUtility->sendWhatsApp('917981009843', $templateParams, '', 'Admin Order Invoice');
            
            return $fileUrl;
        } else {

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
           
            $response = $whatsAppUtility->sendWhatsApp('918961043773', $templateParams, '', 'Admin Order Invoice');
            // $response = $whatsAppUtility->sendWhatsApp('919908570858', $templateParams, '', 'Admin Order Invoice');
            // $response = $whatsAppUtility->sendWhatsApp('917981009843', $templateParams, '', 'Admin Order Invoice');

            return $fileUrl;
        }
    }
}
