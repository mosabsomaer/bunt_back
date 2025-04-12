<?php

namespace App\Services;

use App\Interfaces\FileServiceInterface;
use App\Models\File;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class FileService implements FileServiceInterface
{
    public function __construct(
        protected File $file,
        protected Order $order
    ) {}

    public function getAllFiles()
    {
        return $this->file->all();
    }

    public function getFile(string $id)
    {
        return $this->file->findOrFail($id);
    }

    public function getFilesByOrder(string $orderId)
    {
        $order = $this->order->where('order_id', $orderId)->firstOrFail();
        $files = $this->file->where('order_id', $order->order_id)->get();
        
        if ($files->isEmpty()) {
            throw new \Exception("No files found for the given Order.");
        }

        return [
            'files' => $files,
            'number_pages' => $order->number_pages
        ];
    }

    public function storeFile(array $input)
    {
        $orderId = $input['order_id'];
        $fileCount = $this->file->where('order_id', $orderId)->count();

        if ($fileCount >= 10) {
            throw new \Exception("You can only upload 10 files for this order.");
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CLOUDCONVERT_API_KEY'),
            'Content-Type' => 'application/json',
        ])->get("https://api.cloudconvert.com/v2/jobs/" . $input['JobID']);

        $cloudConvertResponse = $response->json();
        $downloadLink = null;
        $filename = null;
        $PageCount = null;

        foreach ($cloudConvertResponse['data']['tasks'] as $task) {
            if ($task['operation'] === 'export/url') {
                $downloadLink = $task['result']['files'][0]['url'];
                $filename = $task['result']['files'][0]['filename'];
            }
            if ($task['operation'] === 'metadata') {
                $PageCount = $task['result']['metadata']['PageCount'];
            }
        }

        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'pdf') {
            throw new \Exception("Only PDF files are allowed.");
        }

        $fileContent = Http::get($downloadLink)->body();
        $filename = time() . '-' . $filename;
        $storagePath = 'files/' . $filename;
        Storage::put($storagePath, $fileContent);

        $input['path'] = 'files/' . $filename;
        $input['file_name'] = $filename;
        $input['PageCount'] = $PageCount;

        $pricePerPage = $input['color_mode'] ? 1 : 0.5;
        $price = $pricePerPage * $input['PageCount'] * $input['copies'];
        $input['price'] = $price;

        $order = $this->order->where('order_id', $input['order_id'])->first();
        $order->number_pages += $PageCount * $input['copies'];
        $order->save();

        return [
            'file' => $this->file->create($input),
            'order_id' => $order->order_id
        ];
    }

    public function updateFile(string $id, array $input)
    {
        $file = $this->file->findOrFail($id);

        $colorMode = isset($input['color_mode']) ? $input['color_mode'] : $file->color_mode;
        $pricePerPage = $colorMode ? 1 : 0.5;
        $copies = isset($input['copies']) ? $input['copies'] : $file->copies;
        $pageCount = isset($input['PageCount']) ? $input['PageCount'] : $file->PageCount;

        $input['price'] = $pricePerPage * $pageCount * $copies;

        $order = $this->order->where('order_id', $input['order_id'])->firstOrFail();

        $oldcount = $file->copies * $file->PageCount;
        $newcount = $order->number_pages - $oldcount + $copies * $pageCount;

        $order->update(['number_pages' => $newcount]);
        $file->update($input);

        return [
            'file' => $file,
            'total_pages' => $order
        ];
    }

    public function deleteFile(string $id)
    {
        $file = $this->file->findOrFail($id);
        $filepath = $file->path;

        $storageDeleted = Storage::delete($filepath);
        
        $order = $this->order->where('order_id', $file->order_id)->first();
        $order->number_pages -= ($file->PageCount) * $file->copies;
        $order->save();
        
        $file->delete();

        return [
            'storage_deleted' => $storageDeleted,
            'file_path' => $filepath
        ];
    }

    public function downloadOrderFiles(string $orderId)
    {
        $order = $this->order->where('order_id', $orderId)->firstOrFail();
        $files = $this->file->where('order_id', $order->order_id)->get();

        if ($files->isEmpty()) {
            throw new \Exception("No files found for the given Order.");
        }

        $totalPrice = $files->sum('price');
        $zipFileName = 'order_files_' . $order->order_id . '.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception("Could not create zip file.");
        }

        foreach ($files as $file) {
            $filePath = storage_path('app/' . $file->path);
            if (!file_exists($filePath)) {
                throw new \Exception("File not found at the specified path: " . $filePath);
            }
            $relativeName = basename($filePath);
            $zip->addFile($filePath, $relativeName);
        }
        $zip->close();

        return [
            'zip_path' => $zipFilePath,
            'total_price' => $totalPrice
        ];
    }
} 