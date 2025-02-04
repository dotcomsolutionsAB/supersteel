<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver; // Import the correct driver
use Carbon\Carbon;

class ImageDownloadController extends Controller
{
    public function fetchAndSaveImages()
    {
        // Get the current date
        $date = Carbon::now()->format('Y-m-d');
        $date = '2025-01-27';

        // API URL
        $apiUrl = "https://script.google.com/macros/s/AKfycbzdK_vo5rrCicjlFkwCSNIiTlx4IelEcBNb2ZhX53zH3_oJOSTk4J4ovfM1b4lPMj1MHg/exec?date=".$date;

        // Make the POST request
        $response = Http::post($apiUrl);

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
            'CBG' => 'public/storage/uploads/products',
            'WBG' => 'public/storage/uploads/products_pdf',
            'Extra' => 'public/storage/uploads/extra'
        ];

        // Ensure directories exist
        foreach ($folders as $folder) {
            Storage::makeDirectory($folder);
        }

        // Process images
        foreach ($imageData as $category => $imageLinks) {
            if (!is_array($imageLinks)) {
                $this->logImageImport("ERROR: Expected array for category $category, got " . gettype($imageLinks));
                continue;
            }

            foreach ($imageLinks as $imageUrl) {
                $this->downloadAndConvertImage($imageUrl, $folders[$category], $category);
            }
        }

        return response()->json(['message' => 'Images downloaded and saved successfully']);
    }

    private function downloadAndConvertImage($url, $folder, $category)
    {
        try {
            // Get file content
            $imageContent = file_get_contents($url);
            if (!$imageContent) {
                $this->logImageImport("FAILED: Could not download image from $url");
                return;
            }

            // Extract original filename from the URL
            $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
            $originalFilename = $pathInfo['filename'] ?? uniqid(); // Default if no filename found
            $originalExtension = strtolower($pathInfo['extension'] ?? 'jpg'); // Default to JPG if no extension
            $filename = $originalFilename . '.jpg'; // Always save as JPG

            // Save original file temporarily
            $tempPath = storage_path('app/temp_' . $originalFilename . '.' . $originalExtension);
            file_put_contents($tempPath, $imageContent);

            // Load image using Intervention Image
            $manager = new ImageManager(new Driver()); // Use GD Driver
            $image = $manager->read($tempPath);

            // Convert to JPG
            $jpgPath = storage_path('app/' . $originalFilename . '.jpg');

            // ✅ Alternative method: Check extension manually
            if ($originalExtension === 'png') {
                // PNG -> JPG (fill transparent parts with white)
                $canvas = $manager->create($image->width(), $image->height(), 'ffffff');
                $canvas->place($image);
                $canvas->save($jpgPath, quality: 90);
            } else {
                // JPEG or other formats -> Convert to JPG
                $image->save($jpgPath, quality: 90);
            }

            // Move the converted image to the final destination
            Storage::put($folder . '/' . $filename, file_get_contents($jpgPath));

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
