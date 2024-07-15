<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
class OrderController extends Controller
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
            $error = 'Order not found.';
        } elseif ($e instanceof ValidationException) {
            $statusCode = 422;
            $error = $e->errors();
        } else {
            $error = 'An error occurred.';
        }

        return response()->json([
            'error' => $error
        ], $statusCode);
    }
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        try {
            $order = Order::all();
            return response()->json([
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        try {

            $input = null;
            $input['status'] = 'Pending';

            $order = Order::create($input);
            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => [
                    'order_id' => $order->order_id,
                    'status' => $order->status,
                ]
            ], 201);
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
            $order = Order::where('order_id', $id)->first();
            return response()->json([
                'data' => $order
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
            $validStatus = ['Completed', 'Pending', 'Canceled'];
            $order = Order::where('order_id', $id)->first();
            $input = $request->validate([
                'status' => ['string', Rule::in($validStatus)],
                'number_pages' => ['integer'],
            ]);
            $order->update($input);
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
        $order = Order::where('order_id', $id)->firstOrFail();
        $files = File::where('order_id', $order->order_id)->get();

        foreach ($files as $file) {
            // Delete the file from storage
            $filepath = $file->path;
            Storage::delete($filepath);



            $file->delete();
        }

        // Save the updated number_pages (should be zero if all files are deleted)


        // Finally, delete the order
        $order->delete();

        return response()->json([
            'data' => 'Order and associated files deleted'
        ]);
    } catch (\Exception $e) {
        return $this->handleError($e);
    }
}

}
