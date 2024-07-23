<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class MachineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $file = Machine::all();
            return response()->json([
                'data' => $file
            ]);
        } catch (\Exception $e) {
            $error = [
                'error' => 'failed to show all machines'
            ];
            return response()->json($error, 404);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //['required', 'unique:admins,username', 'string'],
        try {
            $validStatus = ['Active', 'Lost Connection', 'Resource Alert'];
            $input = $request->validate([
                'name' => ['required', 'string'],
                'paper' => ['required', 'integer'],
                'coins' => ['required', 'integer'],
                'ink' => ['required', 'integer'],
                'status' => ['required', 'string', Rule::in($validStatus)],
            ]);

            $machine = Machine::create($input);
            return response()->json([
                'status' => 'success',
                'message' => 'Machine created successfully',
                'data' => $machine
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => 'failed to create machine'
            ], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $machine = Machine::findOrFail($id);
            return response()->json([
                'data' => $machine
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'failed to show machine'
            ], 404);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validStatus = ['Active', 'Lost Connection', 'Resource Alert'];
            $machine = Machine::findOrFail($id);
            $input = $request->validate([
                'name' => ['string'],
                'paper' => ['integer'],
                'coins' => ['integer'],
                'ink' => ['integer'],
                'status' => ['string', Rule::in($validStatus)],
            ]);
            $input['last_ping'] = Carbon::now()->format('Y-m-d H:i:s');

            $machine->update($input);
            return response()->json([
                'data' => 'updated',
                'input' => $input,


            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'failed to update machine'
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $machine = Machine::findOrFail($id);
            $machine->delete();
            return response()->json([
                'data' => 'Machine Deleted'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'failed to delete machine'
            ], 404);
        }
    }
}
