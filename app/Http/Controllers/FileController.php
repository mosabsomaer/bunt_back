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
use ZipArchive;

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
            $orderId = $input['order_id'];
            $fileCount = File::where('order_id', $orderId)->count();

            if ($fileCount >= 10) {
                throw new \Exception("You can only upload 10 files for this order.");
                $e='You can only upload 10 files for this order.';
            }
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' .  env('CLOUDCONVERT_API_KEY'),
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
            $order = Order::where('order_id', $input['order_id'])->first();
            $order->number_pages += $PageCount * $input['copies'];
            $order->save();
            File::create($input);
            return response()->json([
                'data' => 'uploaded file on server',
                'order_id' => $order->order_id

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







    public function showByOrder(string $id)
    {
        try {
            $order = Order::where('order_id', $id)->first();
            if (!$order) {
                throw new \Exception("Order not found.");
            }

            $files = File::where('order_id', $order->order_id)->get();
            if ($files->isEmpty()) {
                throw new \Exception("No files found for the given Order.");
            }






            return response()->json([
                'data' => $files,
                'number_pages' => $order->number_pages
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
                'color_mode' => ['nullable', 'boolean'],
                'file_name' => ['string'],
                'JobID' => ['string'],
                'copies' => ['nullable', 'integer', 'min:1'],
                'order_id' => ['string', 'min:6', 'max:6', 'exists:orders,order_id'],
                'PageCount' => ['nullable', 'integer', 'min:1'],
                'path' => ['string'],
            ]);

            $colorMode = isset($input['color_mode']) ? $input['color_mode'] : $file->color_mode;
            $pricePerPage = $colorMode ? 1 : 0.5;
            $copies =  isset($input['copies'])?$input['copies']:$file->copies;
            $pageCount = isset($input['PageCount']) ? $input['PageCount'] : $file->PageCount;

            $input['price'] = $pricePerPage * $pageCount * $copies;

            $order = Order::where('order_id', $input['order_id'])->firstOrFail();



            $oldcount = $file->copies * $file->PageCount;

            $newcount = $order->number_pages - $oldcount + $copies * $pageCount;

            $order->update(['number_pages' => $newcount]);
            $file->update($input);
            return response()->json([
                'data' => 'updated',
                'file'=>$file,
                'total number_pages for the order' => $order
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
            $order->number_pages -= ($file->PageCount) * $file->copies;
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
            $order = Order::where('order_id', $id)->first();
            if (!$order) {
                throw new \Exception("Order not found.");
            }

            $files = File::where('order_id', $order->order_id)->get();
            if ($files->isEmpty()) {
                throw new \Exception("No files found for the given Order.");
            }

            $totalPrice = $files->sum('price');

            $zipFileName = 'order_files_' . $order->order_id . '.zip';
            $zipFilePath = storage_path('app/' . $zipFileName);

            $zip = new ZipArchive;
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                foreach ($files as $file) {
                    $filePath = storage_path('app/' . $file->path);
                    if (file_exists($filePath)) {
                        $relativeName = basename($filePath);
                        $zip->addFile($filePath, $relativeName);
                    } else {
                        throw new \Exception("File not found at the specified path: " . $filePath);
                    }
                }
                $zip->close();
            } else {
                throw new \Exception("Could not create zip file.");
            }

            $response = response()->download($zipFilePath)->deleteFileAfterSend(true);

            $response->headers->set('total_price', $totalPrice);

            return $response;
        } catch (\Exception $e) {
            $error = [
                'error' => $e->getMessage(),
                'total_price' => isset($totalPrice) ? $totalPrice : 0
            ];
            Log::error($e);

            return response()->json($error, 404);
        }
    }
}
