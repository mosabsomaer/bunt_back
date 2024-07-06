<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\File;
use App\Models\Order;

class DownloadController extends Controller
{
    public function downloadFile(string $order_id)
    {
        try {
            // Find the Order using order_id
            $order = Order::where('order_id', $order_id)->first();
            if (!$order) {
                throw new \Exception("Order not found.");
            }

            // Find the File associated with the Order
            $file = File::where('order_id', $order->order_id)->first();
            if (!$file) {
                throw new \Exception("File not found for the given Order.");
            }


        }
      catch (\Exception $e) {
            // Handle the exception
            Log::error($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
