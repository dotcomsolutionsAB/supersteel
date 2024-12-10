<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ProductModel;
use App\Models\User;
use League\Csv\Reader;
use League\Csv\Statement;
use Hash;
use App\Models\CategoryModel;

class CsvImportController extends Controller
{
    //
    public function importProduct()
    {
        // URL of the CSV file from Google Sheets
        $get_product_csv_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSoVot_t3TuRNSNBnz_vCeeeKpMXSap3pPvoers6QuVAIp3Gr32EbE56GSZitCrdGTLudR4vvATlPnD/pub?gid=559356101&single=true&output=csv';

        // Fetch the CSV content using file_get_contents
        $csvContent_product = file_get_contents($get_product_csv_url);

        // Fetch and parse the CSV
        $csv_product = Reader::createFromString($csvContent_product);
        $csv_product->setHeaderOffset(0); // Set the header offset

        $records_csv = (new Statement())->process($csv_product);

        $product_insert_response = null;
        $product_update_response = null;

        // Iterate through each record and create or update the product
        foreach ($records_csv as $record_csv) {
            $product_csv = ProductModel::where('product_code', $record_csv['PRODUCT CODE'])->first();

            $filename = $record_csv['PRODUCT CODE'];

            // Define the product image paths
            $productImagePath = "/storage/uploads/products/{$filename}.jpg";
            $productImagePathPdf = "/storage/uploads/products_pdf/{$filename}.jpg";
            $product_imagePath_for_not_available = "/storage/uploads/products/placeholder.jpg";
            
            // Check if the image exists in the product path
            if (file_exists(public_path($productImagePath))) {
                // Image exists, keep the productImagePath as is
                $productImagePath = $productImagePath;
            } elseif (file_exists(public_path($productImagePathPdf))) {
                // Image doesn't exist in the product path, check in the PDF path
                $productImagePath = $productImagePathPdf;
            } else {
                // If neither image exists, use the placeholder
                $productImagePath = $product_imagePath_for_not_available;
            }

            $productData = [
                'product_code' => $record_csv['PRODUCT CODE'],
                'product_name' => $record_csv['PRODUCT NAME'],
                'print_name' => $record_csv['ITEM PRINT NAME'],
                'brand' => $record_csv['BRAND'],
                'category' => $record_csv['APP CAT'],
                'machine_part_no' => $record_csv['PARENT NAME'],
                'price_a' => (int)str_replace([',', '.00'], '', $record_csv['PRICE A']),
                'price_b' => (int)str_replace([',', '.00'], '', $record_csv['PRICE B']),
                'price_c' => (int)str_replace([',', '.00'], '', $record_csv['PRICE C']),
                'price_d' => (int)str_replace([',', '.00'], '', $record_csv['PRICE D']),
                'price_i' => (int)str_replace([',', '.00'], '', $record_csv['PRICE I']),
                'ppc' => !empty($record_csv['PCS/CTN']) ? (int)$record_csv['PCS/CTN'] : 1,
                'product_image' => $productImagePath,
                'new_arrival' => $record_csv['New Arrival'] === 'TRUE' ? 1 : 0,
                'special_price' => $record_csv['Special Price'] === 'TRUE' ? 1 : 0,
            ];
            
            // Insert or update product
            if ($product_csv) {
                // If product exists, update it
                $product_update_response = $product_csv->update($productData);
            } else {
                // If product does not exist, create a new one
                $product_insert_response = ProductModel::create($productData);
            }
            
        }

        // Return appropriate response
        if ($product_update_response == 1 || isset($product_insert_response)) {
            return response()->json(['message' => 'Products imported successfully'], 200);
        } else {
            return response()->json(['message' => 'Sorry, failed to import successfully'], 404);
        }
    }


    public function importUser()
    {
        // URL of the CSV file from Google Sheets
        $get_product_user_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSoVot_t3TuRNSNBnz_vCeeeKpMXSap3pPvoers6QuVAIp3Gr32EbE56GSZitCrdGTLudR4vvATlPnD/pub?gid=1797389278&single=true&output=csv';
        // $get_product_user_url = 'C:\Users\Dot com\Downloads\dummy_invoice_data.csv';

        // Fetch the CSV content using file_get_contents
        $csvContent_user = file_get_contents($get_product_user_url);

        // Fetch and parse the CSV
        $csv_user = Reader::createFromString($csvContent_user);

        $csv_user->setHeaderOffset(0); // Set the header offset

        $records_user = (new Statement())->process($csv_user);

        $get_insert_response = null;
        $get_update_response = null;

        // Iterate through each record and create or update the product
        foreach ($records_user as $record_user) {

            if (strlen($record_user['Mobile']) == 10) {
                // If it's 10 digits, add '+91' prefix
                $mobile = '+91' . $record_user['Mobile'];
            } elseif (strlen($record_user['Mobile']) == 12) {
                // If it's 12 digits, add '+' prefix
                $mobile = '+' . $record_user['Mobile'];
            } else {
                $mobile = $record_user['Mobile'];
            }

            $user_csv = User::where('mobile', $mobile)->first();

            $manager_name = $record_user['Manager'];

            $manager = User::where('name', $manager_name)->first();
            if ($manager) {
                $manager_id = $manager->id; // Get the manager ID
            } else {
                $manager_id = null;
            }

            // Handle potential empty values for email, pincode, and markup
            $email_user = !empty($record_user['Email']) ? $record_user['Email'] : null;
            $pincode_user = $record_user['Pincode'] !== '' ? $record_user['Pincode'] : null;

            if ($user_csv) 
            {
                // If user exists, update it
                $get_update_response = $user_csv->update([
                    'name' => $record_user['Print Name'],
                    'manager_id' => $manager_id,
                    'alias' => $record_user['Alias'],
                    'email' => $email_user,
                    'password' => bcrypt($mobile),
                    'address_line_1' => $record_user['Address Line 1'],
                    'address_line_2' => $record_user['Address Line 2'],
                    'address_line_3' => $record_user['Address Line 3'],
                    'city' => $record_user['City'],
                    'pincode' => $pincode_user,// Ensure this is a valid number
                    'gstin' => $record_user['GSTIN'],
                    'state' => $record_user['State'],
                    'country' => $record_user['Country'],
                    'billing_style' => $record_user['Billing Style'],
                    'transport' => $record_user['Transport'],
                    'price_type' => strtolower($record_user['PRICE CAT']),

                ]);
            } 
            else 
            {
                // If user does not exist, create a new one
                $get_insert_response = User::create([
                    'mobile' => $mobile,
                    'name' => $record_user['Print Name'],
                    'manager_id' => $manager_id,
                    'alias' => $record_user['Alias'],
                    'email' => $email_user,
                    'password' => bcrypt($mobile),
                    'address_line_1' => $record_user['Address Line 1'],
                    'address_line_2' => $record_user['Address Line 2'],
                    'address_line_3' => $record_user['Address Line 3'],
                    'city' => $record_user['City'],
                    'pincode' => $pincode_user,// Ensure this is a valid number
                    'gstin' => $record_user['GSTIN'],
                    'state' => $record_user['State'],
                    'country' => $record_user['Country'],
                    'billing_style' => $record_user['Billing Style'],
                    'transport' => $record_user['Transport'],
                    'price_type' => strtolower($record_user['PRICE CAT']),
                ]);
            }
        }   

        if ($get_update_response == 1 || isset($get_insert_response)) {
            return response()->json(['message' => 'Users imported successfully'], 200);
        }
        else {
            return response()->json(['message' => 'Sorry, failed to imported successfully'], 404);
        }
    }

    public function importCategory()
    {
        // URL of the CSV file from Google Sheets
        $get_category_csv_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSoVot_t3TuRNSNBnz_vCeeeKpMXSap3pPvoers6QuVAIp3Gr32EbE56GSZitCrdGTLudR4vvATlPnD/pub?gid=1424133495&single=true&output=csv';

        // Fetch the CSV content using file_get_contents
        $csvContent_category = file_get_contents($get_category_csv_url);

        // Fetch and parse the CSV
        $csv_category = Reader::createFromString($csvContent_category);

        $csv_category->setHeaderOffset(0); // Set the header offset
        

        $category_records_csv = (new Statement())->process($csv_category);

        $category_insert_response = null;
        $category_update_response = null;

        // Iterate through each record and create or update the product
        foreach ($category_records_csv as $category_records_csv) {
            $category_csv = CategoryModel::where('name', $category_records_csv['Name'])
            ->where('cat_1', $category_records_csv['CAT 1'])
            ->first();


            // $filename = strtolower(str_replace(' ', '_', $category_records_csv['Name']));
            $filename = $category_records_csv['PHOTO'];

            // Define the product and category image paths
            $categoryImagePath = "/storage/uploads/category/{$filename}.jpg";
            $productImagePath = "/storage/uploads/products_pdf/{$filename}.jpg";
            $placeholderImagePath = "/storage/uploads/category/placeholder.jpg";

            // Check if the category image exists
            if (file_exists(public_path($categoryImagePath))) {
                $imagePath = $categoryImagePath;
            } elseif (file_exists(public_path($productImagePath))) {
                // Check if the product image exists
                $imagePath = $productImagePath;
            } else {
                // Use placeholder if no image is found
                $imagePath = $placeholderImagePath;
            }

            // Use $imagePath as the final image path


            if ($category_csv) 
            {
                // If category exists, update it
                $category_update_response = $category_csv->update([
                    'name' => $category_records_csv['Name'],
                    'cat_1' => $category_records_csv['CAT 1'],
                    'cat_2' => $category_records_csv['CAT 2'],
                    'cat_3' => $category_records_csv['CAT 3'],
                    'category_image' => $imagePath,
                ]);
            } 
            else 
            {
                // If category does not exist, create a new one
                $category_insert_response = CategoryModel::create([
                    'name' => $category_records_csv['Name'],
                    'cat_1' => $category_records_csv['CAT 1'],
                    'cat_2' => $category_records_csv['CAT 2'],
                    'cat_3' => $category_records_csv['CAT 3'],
                    'category_image' => $imagePath,
                ]);
            }
        }   
        if ($category_update_response == 1 || isset($category_insert_response)) {
            return response()->json(['message' => 'Categories imported successfully'], 200);
        }
        else {
            return response()->json(['message' => 'Sorry, failed to import'], 404);
        }
    }


}
