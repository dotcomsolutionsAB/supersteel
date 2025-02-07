<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Carbon\Carbon;

class ImageDownloadController extends Controller
{
    public function fetchAndSaveImages()
    {
        // Get the current date
        $date = Carbon::now()->format('Y-m-d');
        // $date = "2025-02-01";

        // API URL
        $apiUrl = "https://script.google.com/macros/s/AKfycbzdK_vo5rrCicjlFkwCSNIiTlx4IelEcBNb2ZhX53zH3_oJOSTk4J4ovfM1b4lPMj1MHg/exec?date=".$date;

        // Make the POST request
        $response = Http::timeout(180)->post($apiUrl);

        if ($response->failed()) {
            $this->logImageImport("ERROR: Failed to fetch images from API.");
            return response()->json(['error' => 'Failed to fetch images from API'], 500);
        }

        // Decode JSON response
        $imageData = json_decode($response->body(), true);

        // Ensure we have an array, otherwise log error
        if (!is_array($imageData)) {
            $this->logImageImport("ERROR: API returned invalid JSON response: " . $response->body());
            return response()->json(['error' => 'Invalid API response format'], 500);
        }

        // Folder paths
        $folders = [
            'CBG' => 'storage/uploads/products',
            'WBG' => 'storage/uploads/products_pdf',
            'Extra' => 'storage/uploads/extra',
            'LEVEL IMAGES' => 'storage/uploads/category'
        ];

        // Ensure directories exist without changing permissions
        foreach ($folders as $folder) {
            if (!file_exists(public_path($folder))) {
                mkdir(public_path($folder), 0775, true);
            }
        }

        // Process images
        foreach ($imageData as $category => $files) {
            if (!is_array($files)) {
                $this->logImageImport("ERROR: Expected array for category $category, got " . gettype($files));
                continue;
            }

            foreach ($files as $fileData) {
                if (!isset($fileData['filename']) || !isset($fileData['url'])) {
                    $this->logImageImport("ERROR: Missing filename or URL for category $category");
                    continue;
                }

                $this->downloadAndConvertImage($fileData['url'], $folders[$category], $fileData['filename'], $category);
            }
        }

        return response()->json(['message' => 'Images downloaded and saved successfully']);
    }

   private function downloadAndConvertImage($url, $folder, $originalFilename, $category)
    {
        try {
            // Extract file ID from Google Drive URL
            preg_match('/\/d\/(.*?)\//', $url, $matches);
            if (!isset($matches[1])) {
                $this->logImageImport("FAILED: Invalid Google Drive URL format: $url");
                return;
            }

            $fileId = $matches[1];
            $directDownloadUrl = "https://drive.google.com/uc?export=download&id=$fileId";

            // Get file content
            $imageContent = Http::timeout(180)->get($directDownloadUrl)->body();
            if (!$imageContent) {
                $this->logImageImport("FAILED: Could not download image from $directDownloadUrl");
                return;
            }

            // Extract extension from filename
            $originalExtension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            $filenameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);
            $filename = $filenameWithoutExt . '.jpg'; // Always save as JPG

            // Save original file temporarily
            $tempPath = storage_path('app/temp_' . $originalFilename);
            file_put_contents($tempPath, $imageContent);

            // Load image using Intervention Image
            $manager = new ImageManager(new Driver()); // Use GD Driver
            $image = $manager->read($tempPath);

            // Convert to JPG without reducing quality
            $jpgPath = storage_path('app/' . $filename);

            if ($originalExtension === 'png' || $originalExtension === 'webp') {
                // Convert PNG or WEBP to JPG (fill transparent parts with white for PNG)
                $canvas = $manager->create($image->width(), $image->height(), 'ffffff');
                $canvas->place($image);
                $canvas->save($jpgPath, 100); // Max quality
            } else {
                // JPEG or other formats -> Convert to JPG
                $image->save($jpgPath, 100); // Max quality
            }

            // Move the converted image to the final destination
            file_put_contents(public_path($folder . '/' . $filename), file_get_contents($jpgPath));

            // Log successful import
            $this->logImageImport("IMPORTED: [$category] $url -> $folder/$filename");

            // Cleanup temporary files
            unlink($tempPath);
            unlink($jpgPath);

        } catch (\Exception $e) {
            $this->logImageImport("FAILED: $url - Error: " . $e->getMessage());
        }
    }



    private function logImageImport($message)
    {
        $logFile = storage_path('logs/image_import.log');
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;

        // Read existing logs
        $existingLogs = file_exists($logFile) ? file_get_contents($logFile) : '';

        // Prepend new log entry
        file_put_contents($logFile, $logEntry . $existingLogs);
    }
}
