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
        $get_product_csv_url = 'https://docs.google.com/spreadsheets/d/1_4XMqLfR7EqOWMxrilnCZq5-YuYn1dRLlPbFIl41OsU/pub?gid=0&single=true&output=csv';

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
            $product_csv = ProductModel::where('product_code', $record_csv['Product Code'])->first();

            $filename = $record_csv['Product Code'];

            // Define the product image path and check if the image exists
            $productImagePath = "/storage/uploads/products/{$filename}.jpg";
            $product_imagePath_for_not_available = "/storage/uploads/products/placeholder.jpg";

            if (!file_exists(public_path($productImagePath))) {
                $productImagePath = $product_imagePath_for_not_available; // Use placeholder if image not found
            }

            // Step 1: Convert the string into an array, and remove empty values (caused by trailing commas)
            $cat_array = array_filter(explode(',', $record_csv['Category']));

            // Step 2: Check if the array has elements
            if (count($cat_array) > 1)
            {
                // Reverse the remaining array elements (except the last one) to complete the rotation
                $cat_array = array_reverse($cat_array);
            }

            // Store categories into columns c1, c2, c3, c4, and c5
            $category_column = [
                'c1' => $cat_array[0] ?? null,
                'c2' => $cat_array[1] ?? null,
                'c3' => $cat_array[2] ?? null,
                'c4' => $cat_array[3] ?? null,
                'c5' => $cat_array[4] ?? null,
            ];


            foreach($cat_array as $index => $category_value)
            {

                $category = CategoryModel::where('code', $category_value)->first();

                // If category is found, update the level
                if($category)
                {
                    $category->update([
                        'level' => $index + 1 // Set the level based on the array position
                    ]);
                }
            }

            // Step 4: Convert the array into JSON format
            // $jsonArray = json_encode($cat_array);


            if ($product_csv) 
            {
                // If product exists, update it
                $product_update_response = $product_csv->update(array_merge([
                    'product_code' => $record_csv['Product Code'],
                    'product_name' => $record_csv['Product Name'],
                    'print_name' => $record_csv['Print Name'],
                    'brand' => $record_csv['Brand'],
                    'category' => $record_csv['App Category'],
                    'sub_category' => $record_csv['App Sub Categoy'],
                    // 'category' => $jsonArray,
                    // 'type' => $record_csv['Type'],
                    'machine_part_no' => $record_csv['Machine Part No.'],
                    'price_a' => $record_csv['Price A'],
                    'price_b' => $record_csv['Price B'],
                    'price_c' => $record_csv['Price C'],
                    'price_d' => $record_csv['Price D'],
                    'price_i' => $record_csv['Price I'],
                    'product_image' => $productImagePath,
                ], $category_column));
            } 
            else 
            {
                // If product does not exist, create a new one
                $product_insert_response = ProductModel::create(array_merge([
                    'product_code' => $record_csv['Product Code'],
                    'product_name' => $record_csv['Product Name'],
                    'print_name' => $record_csv['Print Name'],
                    'brand' => $record_csv['Brand'],
                    'category' => $record_csv['App Category'],
                    'sub_category' => $record_csv['App Sub Categoy'],
                    // 'category' => $record_csv['Category'],
                    // 'category' => $jsonArray,
                    // 'type' => $record_csv['Type'],
                    'machine_part_no' => $record_csv['Machine Part No.'],
                    'price_a' => $record_csv['Price A'],
                    'price_b' => $record_csv['Price B'],
                    'price_c' => $record_csv['Price C'],
                    'price_d' => $record_csv['Price D'],
                    'price_i' => $record_csv['Price I'],
                    'product_image' => $productImagePath,
                ], $category_column));

            }
        }   
        if ($product_update_response == 1 || isset($product_insert_response)) {
            return response()->json(['message' => 'Products imported successfully'], 200);
        }
        else {
            return response()->json(['message' => 'Sorry, failed to imported successfully'], 404);
        }
    }

    public function importUser()
    {
        // URL of the CSV file from Google Sheets
        // $get_product_user_url = 'https://docs.google.com/spreadsheets/d/1_4XMqLfR7EqOWMxrilnCZq5-YuYn1dRLlPbFIl41OsU/pub?gid=1797389278&single=true&output=csv';
        $get_product_user_url = 'C:\Users\Dot com\Downloads\dummy_invoice_data.csv';

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

            // Handle potential empty values for email, pincode, and markup
            $email_user = !empty($record_user['Email']) ? $record_user['Email'] : null;
            $pincode_user = $record_user['Pincode'] !== '' ? $record_user['Pincode'] : 0;

            if ($user_csv) 
            {
                // If user exists, update it
                $get_update_response = $user_csv->update([
                    'name' => $record_user['Name'],
                    'email' => $email_user,
                    'password' => bcrypt($mobile),
                    'address_line_1' => $record_user['Address Line 1'],
                    'address_line_2' => $record_user['Address Line 2'],
                    'city' => $record_user['City'],
                    'pincode' => $pincode_user,// Ensure this is a valid number
                    'gstin' => $record_user['GSTIN'],
                    'state' => $record_user['State'],
                    'country' => $record_user['Country'],
                ]);
            } 
            else 
            {
                // If user does not exist, create a new one
                $get_insert_response = User::create([
                    'mobile' => $mobile,
                    'name' => $record_user['Name'],
                    'email' => $email_user,
                    'password' => bcrypt($mobile),
                    'address_line_1' => $record_user['Address Line 1'],
                    'address_line_2' => $record_user['Address Line 2'],
                    'city' => $record_user['City'],
                    'pincode' => $pincode_user,// Ensure this is a valid number
                    'gstin' => $record_user['GSTIN'],
                    'state' => $record_user['State'],
                    'country' => $record_user['Country'],
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
        $get_category_csv_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSoVot_t3TuRNSNBnz_vCeeeKpMXSap3pPvoers6QuVAIp3Gr32EbE56GSZitCrdGTLudR4vvATlPnD/pub?gid=1216834895&single=true&output=csv';

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
            $category_csv = CategoryModel::where('code', $category_records_csv['Product Code'])->first();

            $filename = $category_records_csv['Product Code'];

            // Define the product image path and check if the image exists
            $categoryImagePath = "/storage/uploads/category/{$filename}.jpg";
            $category_imagePath_for_not_available = "/storage/uploads/category/placeholder.jpg";

            if (!file_exists(public_path($categoryImagePath))) {
                $categoryImagePath = $category_imagePath_for_not_available; // Use placeholder if image not found
            }

            if ($category_csv) 
            {
                // If category exists, update it
                $category_update_response = $product_csv->update([
                    'code' => $category_records_csv['CODE'],
                    'product_code' => $category_records_csv['Product Code'],
                    'category_image' => $categoryImagePath,
                ]);
            } 
            else 
            {
                // If category does not exist, create a new one
                $category_insert_response = CategoryModel::create([
                    'code' => $category_records_csv['CODE'],
                    'product_code' => $category_records_csv['Product Code'],
                    'category_image' => $categoryImagePath,
                ]);
            }
        }   
        if ($category_update_response == 1 || isset($category_insert_response)) {
            return response()->json(['message' => 'Category imported successfully'], 200);
        }
        else {
            return response()->json(['message' => 'Sorry, failed to import'], 404);
        }
    }
}