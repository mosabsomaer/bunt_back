<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Order;
use App\Models\Machine;
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

            foreach ($order as $ord) {
                $files = File::where('order_id', $ord->order_id)->get();
                if ($files->isEmpty()) {
                    throw new \Exception("No files found for the given Order.");
                }

                $ord['price'] = $files->sum('price');
                $ord['files'] = count($files);
            }

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
                'status' => ['required', 'string', Rule::in($validStatus)],
                'number_pages' => ['integer'],
            ]);
            if ($input['status'] == 'Completed') {

                    $files = File::where('order_id', $id)->get();
                // put this back when your done as it will delete all the files once the files have been printed and it wont delete the file in the table
                //     foreach ($files as $file) {
                //         // Delete the file from storage
                //         $filepath = $file->path;
                //         Storage::delete($filepath);
                //     }
               $price = $files->sum('price');
                $machine = Machine::findOrFail(2);
                $inputm=[];
                $inputm['paper']=$machine->paper-$order->number_pages;
                $inputm['coins']=$machine->coins+$price;
                $jj=$order->number_pages;
                $kk=$jj*100/1000;
                $inputm['ink']=$machine->ink-$kk;
                $machine->update($inputm);
            }
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
