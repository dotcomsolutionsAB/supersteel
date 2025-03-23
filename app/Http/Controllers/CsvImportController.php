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

        ProductModel::query()->update(['is_active' => 0]);

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

            // Handle extra images
            $extraImages = [];
            $extraImagePath = public_path("/storage/uploads/extra");
            $i = 1;

            // Check for extra images in the format PRODUCT_CODE-1, PRODUCT_CODE-2, etc.
            while (file_exists("{$extraImagePath}/{$filename}-{$i}.jpg")) {
                $extraImages[] = "/storage/uploads/extra/{$filename}-{$i}.jpg";
                $i++;
            }

            // Convert extra images array to a comma-separated string
            $extraImagesCsv = implode(',', $extraImages);

            $productData = [
                'product_code' => $record_csv['PRODUCT CODE'],
                'product_name' => $record_csv['PRODUCT NAME'],
                'print_name' => $record_csv['ITEM PRINT NAME'],
                'brand' => $record_csv['BRAND'],
                'category' => $record_csv['APP CAT'],
                'machine_part_no' => $record_csv['PARENT NAME'],
                'price_a' => str_replace([',', '.00'], '', $record_csv['PRICE A']),
                'price_b' => str_replace([',', '.00'], '', $record_csv['PRICE B']),
                'price_c' => str_replace([',', '.00'], '', $record_csv['PRICE C']),
                'price_d' => str_replace([',', '.00'], '', $record_csv['PRICE D']),
                'price_i' => str_replace([',', '.00'], '', $record_csv['PRICE I']),
                'supplier' => $record_csv['SUPPLIER'],
                're_order_level' => $record_csv['RE-ORDER LEVEL'],
                'ppc' => !empty($record_csv['PCS/CTN']) && $record_csv['PCS/CTN'] !== '-' ? $record_csv['PCS/CTN'] : '1', // Avoid (int) conversion
                'stock' => $record_csv['STOCK'],
                'in_transit' => $record_csv['IN TRANSIT'],
                'pending' => $record_csv['PENDING'],
                'product_image' => $productImagePath,
                'extra_images' => $extraImagesCsv, // Set the extra images
                'new_arrival' => $record_csv['New Arrival'] === 'TRUE' ? 1 : 0,
                'special_price' => $record_csv['Special Price'] === 'TRUE' ? 1 : 0,
                'video_link' => $record_csv['YouTube Link'],
                'is_active' => $record_csv['Active'] === 'TRUE' ? 1 : 0,
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


    // public function importUser()
    // {
    //     // URL of the CSV file from Google Sheets
    //     $get_product_user_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSoVot_t3TuRNSNBnz_vCeeeKpMXSap3pPvoers6QuVAIp3Gr32EbE56GSZitCrdGTLudR4vvATlPnD/pub?gid=1797389278&single=true&output=csv';

    //     // Fetch the CSV content using file_get_contents
    //     $csvContent_user = file_get_contents($get_product_user_url);

    //     // Fetch and parse the CSV
    //     $csv_user = Reader::createFromString($csvContent_user);
    //     $csv_user->setHeaderOffset(0);

    //     $records_user = (new Statement())->process($csv_user);

    //     // Pre-fetch existing users and managers
    //     $existingUsers = User::whereIn('mobile', collect($records_user)->pluck('Mobile')->map(function ($mobile) {
    //         return strlen($mobile) == 10 ? '+91' . $mobile : (strlen($mobile) == 12 ? '+' . $mobile : $mobile);
    //     }))->orWhereIn('mobile', collect($records_user)->pluck('Secondary Mobile')->map(function ($mobile) {
    //         return strlen($mobile) == 10 ? '+91' . $mobile : (strlen($mobile) == 12 ? '+' . $mobile : $mobile);
    //     }))->get()->keyBy('mobile');

    //     $managerNames = collect($records_user)->pluck('Manager')->unique()->filter();
    //     $existingManagers = User::whereIn('name', $managerNames)->get()->keyBy('name');

    //     $insertData = [];
    //     $updateData = [];

    //     foreach ($records_user as $record_user) {
    //         $mobile = strlen($record_user['Mobile']) == 10 ? '+91' . $record_user['Mobile'] : (strlen($record_user['Mobile']) == 12 ? '+' . $record_user['Mobile'] : $record_user['Mobile']);
    //         $secondaryMobile = isset($record_user['Secondary Mobile']) && $record_user['Secondary Mobile'] !== ''
    //             ? (strlen($record_user['Secondary Mobile']) == 10 ? '+91' . $record_user['Secondary Mobile'] : (strlen($record_user['Secondary Mobile']) == 12 ? '+' . $record_user['Secondary Mobile'] : $record_user['Secondary Mobile']))
    //             : null;

    //         if (!$mobile) continue; // Skip invalid primary mobile numbers

    //         $manager_id = isset($existingManagers[$record_user['Manager']]) ? $existingManagers[$record_user['Manager']]->id : null;

    //         $notifications = isset($record_user['Notifications']) && strtolower($record_user['Notifications']) === 'true' ? 1 : 0;

    //         $commonData = [
    //             'name' => $record_user['Print Name'],
    //             'manager_id' => $manager_id,
    //             'alias' => $record_user['Alias'],
    //             'email' => $record_user['Email'] ?? null,
    //             'password' => bcrypt($mobile),
    //             'address_line_1' => $record_user['Address Line 1'],
    //             'address_line_2' => $record_user['Address Line 2'],
    //             'address_line_3' => $record_user['Address Line 3'],
    //             'city' => $record_user['City'],
    //             'pincode' => $record_user['Pincode'] !== '' ? $record_user['Pincode'] : null,
    //             'gstin' => $record_user['GSTIN'],
    //             'state' => $record_user['State'],
    //             'billing_style' => $record_user['Billing Style'],
    //             'transport' => $record_user['Transport'],
    //             'notifications' => $notifications,
    //         ];

    //         // Update or insert primary mobile user
    //         $user = $existingUsers[$mobile] ?? null;
    //         $primaryData = array_merge($commonData, ['price_type' => strtolower($record_user['PRICE CAT'])]);

    //         if ($user) {
    //             // Prepare for bulk update if any data has changed
    //             $updateData[$mobile] = array_merge($primaryData, ['id' => $user->id]);
    //         } else {
    //             // Prepare for bulk insert
    //             $insertData[] = array_merge(['mobile' => $mobile], $primaryData);
    //         }

    //         // Update or insert secondary mobile user
    //         if ($secondaryMobile) {
    //             $secondaryUser = $existingUsers[$secondaryMobile] ?? null;
    //             $secondaryData = array_merge($commonData, ['price_type' => 'zero_price']);

    //             if ($secondaryUser) {
    //                 $updateData[$secondaryMobile] = array_merge($secondaryData, ['id' => $secondaryUser->id]);
    //             } else {
    //                 $insertData[] = array_merge(['mobile' => $secondaryMobile], $secondaryData);
    //             }
    //         }
    //     }

    //     // Bulk insert new users
    //     if (!empty($insertData)) {
    //         User::insert($insertData);
    //     }

    //     // Bulk update existing users
    //     if (!empty($updateData)) {
    //         foreach ($updateData as $data) {
    //             User::where('id', $data['id'])->update($data);
    //         }
    //     }

    //     return response()->json(['message' => 'Users imported successfully'], 200);
    // }

    public function importUser()
    {
        $get_product_user_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSoVot_t3TuRNSNBnz_vCeeeKpMXSap3pPvoers6QuVAIp3Gr32EbE56GSZitCrdGTLudR4vvATlPnD/pub?gid=1797389278&single=true&output=csv';

        $csvContent_user = file_get_contents($get_product_user_url);

        $csv_user = Reader::createFromString($csvContent_user);
        $csv_user->setHeaderOffset(0);

        $records_user = (new Statement())->process($csv_user);

        // Step 1: Fetch all users and build a map with 'alias|mobile' as key
        $allUsers = User::all()->keyBy(function ($user) {
            return strtolower($user->alias . '|' . $user->mobile);
        });

        $managerNames = collect($records_user)->pluck('Manager')->unique()->filter();
        $existingManagers = User::whereIn('name', $managerNames)->get()->keyBy('name');

        $insertData = [];
        $updateData = [];

        foreach ($records_user as $record_user) {
            $alias = strtolower($record_user['Alias']);

            $mobile = strlen($record_user['Mobile']) == 10 ? '+91' . $record_user['Mobile'] : (strlen($record_user['Mobile']) == 12 ? '+' . $record_user['Mobile'] : $record_user['Mobile']);
            $secondaryMobile = isset($record_user['Secondary Mobile']) && $record_user['Secondary Mobile'] !== ''
                ? (strlen($record_user['Secondary Mobile']) == 10 ? '+91' . $record_user['Secondary Mobile'] : (strlen($record_user['Secondary Mobile']) == 12 ? '+' . $record_user['Secondary Mobile'] : $record_user['Secondary Mobile']))
                : null;

            if (!$mobile || !$alias) continue; // skip if no alias or invalid mobile

            $manager_id = isset($existingManagers[$record_user['Manager']]) ? $existingManagers[$record_user['Manager']]->id : null;

            $notifications = isset($record_user['Notifications']) && strtolower($record_user['Notifications']) === 'true' ? 1 : 0;

            $commonData = [
                'name' => $record_user['Print Name'],
                'manager_id' => $manager_id,
                'alias' => $record_user['Alias'],
                'email' => $record_user['Email'] ?? null,
                'password' => bcrypt($mobile),
                'address_line_1' => $record_user['Address Line 1'],
                'address_line_2' => $record_user['Address Line 2'],
                'address_line_3' => $record_user['Address Line 3'],
                'city' => $record_user['City'],
                'pincode' => $record_user['Pincode'] !== '' ? $record_user['Pincode'] : null,
                'gstin' => $record_user['GSTIN'],
                'state' => $record_user['State'],
                'billing_style' => $record_user['Billing Style'],
                'transport' => $record_user['Transport'],
                'notifications' => $notifications,
            ];

            // 🔍 Check if primary mobile + alias already exists
            $key = strtolower($alias . '|' . $mobile);
            $user = $allUsers[$key] ?? null;
            $primaryData = array_merge($commonData, ['price_type' => strtolower($record_user['PRICE CAT'])]);

            if ($user) {
                $updateData[$key] = array_merge($primaryData, ['id' => $user->id]);
            } else {
                $insertData[] = array_merge(['mobile' => $mobile], $primaryData);
            }

            // 🔍 Check if secondary mobile + alias already exists
            if ($secondaryMobile) {
                $secondaryKey = strtolower($alias . '|' . $secondaryMobile);
                $secondaryUser = $allUsers[$secondaryKey] ?? null;
                $secondaryData = array_merge($commonData, ['price_type' => 'zero_price']);

                if ($secondaryUser) {
                    $updateData[$secondaryKey] = array_merge($secondaryData, ['id' => $secondaryUser->id]);
                } else {
                    $insertData[] = array_merge(['mobile' => $secondaryMobile], $secondaryData);
                }
            }
        }

        // ✅ Insert new users
        if (!empty($insertData)) {
            User::insert($insertData);
        }

        // ✅ Update existing users
        if (!empty($updateData)) {
            foreach ($updateData as $data) {
                User::where('id', $data['id'])->update($data);
            }
        }

        return response()->json(['message' => 'Users imported successfully'], 200);
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
            $filename = $category_records_csv['CAT 1'].$category_records_csv['CAT 2'].$category_records_csv['CAT 3'];

            // Define the product and category image paths
            $categoryImagePath = "/storage/uploads/category/{$filename}.jpg";
            $productImagePath = "/storage/uploads/products_pdf/{$filename}.jpg";
            $placeholderImagePath = "/storage/uploads/category/placeholder.jpg";

            // Check if the category image exists
            if (file_exists(public_path($categoryImagePath))) {
                $imagePath = $categoryImagePath;
            } 
            elseif (file_exists(public_path($productImagePath))) {
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
