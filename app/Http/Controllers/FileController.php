<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{

    /**
     * Handle the error response.
     *
     * @param \Exception $e
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleError(\Exception $e, $statusCode = 500)
    {
        if ($e instanceof ModelNotFoundException) {
            $statusCode = 404;
            $error = 'File not found.';
        } elseif ($e instanceof ValidationException) {
            $statusCode = 422;
            $error = $e->errors();
        } else {
            $error = 'An error occurred.';
        }

        return response()->json([
            'error' => $error, $e
        ], $statusCode);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $file = File::all();
            return response()->json([
                'data' => $file
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    //thats how to name your file
    //you have to make sure that the file name doesnt contain "/" or it might cause you problems when you want to delete the file or retrive it
    public function store(Request $request)
    {
        try {
            $input = $request->validate([
                'JobID' => ['required', 'string'],
                'copies' => ['required', 'integer', 'min:1'],
                'color_mode' => ['required', 'boolean'],
                'order_id' => ['required', 'string', 'min:6', 'max:6', 'exists:orders,order_id'],

            ]);

            // Make the GET request to the CloudConvert API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' .  env('CLOUDCONVERT_API_KEY'),
                'Content-Type' => 'application/json',
            ])->get("https://api.cloudconvert.com/v2/jobs/" . $input['JobID']);
            $cloudConvertResponse = $response->json();

            // Extract the download link from the response
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

            // Download the file content
            $fileContent = Http::get($downloadLink)->body();

            // Define the filename and store the file
            $filename = time() . '-' . $filename;

            $storagePath = 'files/' . $filename;
            Storage::put($storagePath, $fileContent);

            // Add the file path to the input array
            $input['path'] = 'files/' . $filename;
            $input['file_name'] = $filename;
            $input['PageCount'] = $PageCount;

            // Calculate the price
            $pricePerPage = $input['color_mode'] ? 1 : 0.5;
            $price = $pricePerPage * $input['PageCount'] * $input['copies'];
            // Add the calculated price to the input array
            $input['price'] = $price;
            //update number of pages in order table
            $order = Order::where('order_id', $input['order_id'])->first();
            $order->number_pages += $PageCount;
            $order->save();
            // Create the file record in the database
            File::create($input);
            return response()->json([
                'data' => 'created',
                'downloadLink' => $downloadLink,
                'api_response' => $cloudConvertResponse,
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }




    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $file = File::findOrFail($id);
            return response()->json([
                'data' => $file
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $file = File::findOrFail($id);
            $input = $request->validate([
                'color_mode' => ['boolean'],
                'file_name' => ['string'],
                'JobID' => ['string'],
                'copies' => ['integer', 'min:1'],
                'order_id' => ['string', 'min:6', 'max:6', 'exists:orders,order_id'],
                'PageCount' => ['integer', 'min:1'],
                'path' => ['string'],
            ]);

            $pricePerPage = $input['color_mode'] ? 1 : 0.5;
            $input['price']  = $pricePerPage * $input['PageCount'] * $input['copies'];

            $order = Order::where('order_id', $input['order_id'])->first();

            $order->number_pages += $input['PageCount'] - $file->PageCount;;
            $order->save();
            $file->update($input);
            return response()->json([
                'data' => 'updated'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $file = File::findOrFail($id);
            $filepath = $file->path;

            $hello = Storage::delete($filepath);
            $order = Order::where('order_id', $file->order_id)->first();
            $order->number_pages -= $file->PageCount;
            $order->save();
            $file->delete();
            return response()->json([
                'data' => 'File Deleted',
                'file deleted from folder' => $hello,
                'file path' => $filepath
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    public function downloadFile(string $id)
    {
        try {
            // Find the Order using order_id
            $order = Order::where('order_id', $id)->first();
            if (!$order) {
                throw new \Exception("Order not found.");
            }

            // Find the File associated with the Order
            $file = File::where('order_id', $order->order_id)->first();
            if (!$file) {
                throw new \Exception("File not found for the given Order.");
            }

            $filePath = storage_path('app/' . $file->path);

            // Check if the file exists
            if (!file_exists($filePath)) {
                throw new \Exception("File not found at the specified path.");
            }

            // Return the file for download
            return response()->download($filePath);
        } catch (\Exception $e) {
            $error = [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'path' => $filePath ?? null
            ];

            // Log the error for further investigation
            Log::error($e);

            // Return the error response
            return response()->json($error, 500);
        }
    }
}
