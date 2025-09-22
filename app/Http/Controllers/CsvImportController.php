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
        try {
            // URL of the CSV file from Google Sheets
            $get_product_csv_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSoVot_t3TuRNSNBnz_vCeeeKpMXSap3pPvoers6QuVAIp3Gr32EbE56GSZitCrdGTLudR4vvATlPnD/pub?gid=559356101&single=true&output=csv';

            // Fetch the CSV content using file_get_contents
            $csvContent_product = file_get_contents($get_product_csv_url);

            // Fetch and parse the CSV
            $csv_product = Reader::createFromString($csvContent_product);
            $csv_product->setHeaderOffset(0); // Set the header offset

            $records_csv = (new Statement())->process($csv_product);

            ProductModel::query()->update(['is_active' => 2]);

            $product_insert_response = null;
            $product_update_response = null;

            $sn = 1;

            // Set all to 0 first
            CategoryModel::query()->update(['preview' => 0]);
            $previewCategoryNames = [];

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
                    'sn' => $sn++,
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
                    'price_j' => str_replace([',', '.00'], '', $record_csv['PRICE J']),
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
                    'preview' => $record_csv['Preview'] === 'TRUE' ? 1 : 0,
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

                if ($productData['preview'] == 1 && !empty($productData['category'])) {
                    $previewCategoryNames[] = $productData['category'];
                }
                
            }

            $previewCategoryNames = array_unique($previewCategoryNames);

            // DEBUG: Log or dump which categories are being used
            // \Log::info('Categories to update as preview:', $previewCategoryNames);

            // if (!empty($previewCategoryNames)) {
            //     CategoryModel::whereIn('cat_2', $previewCategoryNames)->update(['preview' => 1]);
            //     CategoryModel::whereIn('cat_2', '')->update(['preview' => 1]);
            // }

            // Update categories where cat_2 is in $previewCategoryNames OR
            // cat_2 is NULL or empty string
            if (!empty($previewCategoryNames)) {
                CategoryModel::where(function($query) use ($previewCategoryNames) {
                    $query->whereIn('cat_2', $previewCategoryNames)
                        ->orWhereNull('cat_2')
                        ->orWhere('cat_2', '');
                })->update(['preview' => 1]);
            }

            // Return appropriate response
            if ($product_update_response == 1 || isset($product_insert_response)) {
                return response()->json(['message' => 'Products imported successfully'], 200);
            } else {
                return response()->json(['message' => 'Sorry, failed to import successfully'], 404);
            }

        } catch (\Exception $e) {
            // Log the full error for debugging
            // \Log::error('Product import failed:', [
            //     'message' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
            return response()->json([
                'message' => 'An error occurred during import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function importUser()
    {
        $get_product_user_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSoVot_t3TuRNSNBnz_vCeeeKpMXSap3pPvoers6QuVAIp3Gr32EbE56GSZitCrdGTLudR4vvATlPnD/pub?gid=1797389278&single=true&output=csv';

        $csvContent_user = file_get_contents($get_product_user_url);
        $csv_user = Reader::createFromString($csvContent_user);
        $csv_user->setHeaderOffset(0);
        $records_user = (new Statement())->process($csv_user);

        // Pre-fetch users from DB and key them by alias|mobile
        $allUsers = User::select('id', 'alias', 'mobile')->get()->keyBy(function ($user) {
            return strtolower(trim($user->alias) . '|' . trim($user->mobile));
        });

        // Get managers
        $managerNames = collect($records_user)->pluck('Manager')->unique()->filter();
        $existingManagers = User::whereIn('name', $managerNames)->get()->keyBy('name');

        $insertData = [];
        $updateData = [];

        foreach ($records_user as $record_user) {
            $alias = strtolower(trim($record_user['Alias']));
            if (!$alias) continue;

            $mobile = strlen($record_user['Mobile']) == 10 ? '+91' . $record_user['Mobile'] : (strlen($record_user['Mobile']) == 12 ? '+' . $record_user['Mobile'] : $record_user['Mobile']);
            $secondaryMobile = isset($record_user['Secondary Mobile']) && $record_user['Secondary Mobile'] !== ''
                ? (strlen($record_user['Secondary Mobile']) == 10 ? '+91' . $record_user['Secondary Mobile'] : (strlen($record_user['Secondary Mobile']) == 12 ? '+' . $record_user['Secondary Mobile'] : $record_user['Secondary Mobile']))
                : null;

            // if (!$mobile) continue;

            $manager_id = isset($existingManagers[$record_user['Manager']]) ? $existingManagers[$record_user['Manager']]->id : null;
            $notifications = isset($record_user['Notifications']) && strtolower($record_user['Notifications']) === 'true' ? 1 : 0;

            $commonData = [
                'name' => $record_user['Print Name'],
                'manager_id' => $manager_id,
                'alias' => $record_user['Alias'],
                'email' => $record_user['Email'] ?? null,
                'password' => $mobile,
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

            // PRIMARY MOBILE
            $primaryKey = strtolower(trim($alias) . '|' . trim($mobile));
            $primaryData = array_merge($commonData, ['price_type' => strtolower($record_user['PRICE CAT'])]);

            if (isset($allUsers[$primaryKey])) {
                $updateData[$primaryKey] = array_merge($primaryData, ['id' => $allUsers[$primaryKey]->id]);
            } else {
                $insertData[] = array_merge(['mobile' => $mobile], $primaryData);
            }

            // SECONDARY MOBILE
            if ($secondaryMobile) {
                $secondaryKey = strtolower(trim($alias) . '|' . trim($secondaryMobile));
                $secondaryData = array_merge($commonData, ['price_type' => 'zero_price']);

                if (isset($allUsers[$secondaryKey])) {
                    $updateData[$secondaryKey] = array_merge($secondaryData, ['id' => $allUsers[$secondaryKey]->id]);
                } else {
                    $insertData[] = array_merge(['mobile' => $secondaryMobile], $secondaryData);
                }
            }
        }

        // INSERT new users
        if (!empty($insertData)) {
            User::insert($insertData);
        }

        // UPDATE existing users
        if (!empty($updateData)) {
            foreach ($updateData as $data) {
                User::where('id', $data['id'])->update($data);
            }
        }

        return response()->json(['message' => 'Users imported successfully'], 200);
    }

    public function importCategory()
    {
        $get_category_csv_url = 'https://docs.google.com/spreadsheets/d/e/2PACX-1vSoVot_t3TuRNSNBnz_vCeeeKpMXSap3pPvoers6QuVAIp3Gr32EbE56GSZitCrdGTLudR4vvATlPnD/pub?gid=1424133495&single=true&output=csv';

        $csvContent_category = file_get_contents($get_category_csv_url);
        $csv_category = Reader::createFromString($csvContent_category);
        $csv_category->setHeaderOffset(0);

        $category_records_csv = (new Statement())->process($csv_category);

        $updated = 0;
        $inserted = 0;

        foreach ($category_records_csv as $record) {
            $filename = $record['CAT 1'] . $record['CAT 2'] . $record['CAT 3'];
            $categoryImagePath = "/storage/uploads/category/{$filename}.jpg";
            $productImagePath = "/storage/uploads/products_pdf/{$filename}.jpg";
            $placeholderImagePath = "/storage/uploads/category/placeholder.jpg";

            // Determine final image path
            if (file_exists(public_path($categoryImagePath))) {
                $imagePath = $categoryImagePath;
            } elseif (file_exists(public_path($productImagePath))) {
                $imagePath = $productImagePath;
            } else {
                $imagePath = $placeholderImagePath;
            }

            // 🔍 Check if a category with all 3 cat values exists
            $existing = CategoryModel::where('cat_1', $record['CAT 1'])
                ->where('cat_2', $record['CAT 2'])
                ->where('cat_3', $record['CAT 3'])
                ->first();

            if ($existing) {
                $existing->update([
                    'name' => $record['Name'],
                    'category_image' => $imagePath,
                ]);
                $updated++;
            } else {
                CategoryModel::create([
                    'name' => $record['Name'],
                    'cat_1' => $record['CAT 1'],
                    'cat_2' => $record['CAT 2'],
                    'cat_3' => $record['CAT 3'],
                    'category_image' => $imagePath,
                ]);
                $inserted++;
            }
        }

        if ($updated || $inserted) {
            return response()->json([
                'message' => 'Categories imported successfully',
                'updated' => $updated,
                'inserted' => $inserted
            ], 200);
        } else {
            return response()->json(['message' => 'No categories were imported or updated.'], 404);
        }
    }

}
