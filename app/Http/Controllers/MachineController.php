<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Services\MachineService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Http\Requests\Machine\StoreMachineRequest;
use App\Http\Requests\Machine\UpdateMachineRequest;
use Illuminate\Http\JsonResponse;

class MachineController extends Controller
{
    public function __construct(
        protected MachineService $machineService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $this->authorize('viewAny', Machine::class);
            $machines = $this->machineService->getAllMachines();
            return response()->json([
                'status' => 'success',
                'data' => $machines
            ]);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'error' => 'Unauthorized to view machines'
                ], 403);
            }
            return response()->json([
                'error' => 'Failed to show all machines'
            ], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMachineRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Machine::class);
            $machine = $this->machineService->createMachine($request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Machine created successfully',
                'data' => $machine
            ], 201);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'error' => 'Unauthorized to create machine'
                ], 403);
            }
            return response()->json([
                'error' => 'Failed to create machine'
            ], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $machine = $this->machineService->getMachine($id);
            $this->authorize('view', $machine);
            return response()->json([
                'status' => 'success',
                'data' => $machine
            ]);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'error' => 'Unauthorized to view this machine'
                ], 403);
            }
            return response()->json([
                'error' => 'Failed to show machine'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMachineRequest $request, string $id): JsonResponse
    {
        try {
            $machine = $this->machineService->getMachine($id);
            $this->authorize('update', $machine);
            $machine = $this->machineService->updateMachine($id, $request->validated());
            return response()->json([
                'status' => 'success',
                'message' => 'Machine updated successfully',
                'data' => $machine
            ]);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'error' => 'Unauthorized to update this machine'
                ], 403);
            }
            return response()->json([
                'error' => 'Failed to update machine'
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $machine = $this->machineService->getMachine($id);
            $this->authorize('delete', $machine);

            $this->machineService->deleteMachine($id);
            return response()->json([
                'status' => 'success',
                'message' => 'Machine deleted successfully'
            ]);
        } catch (\Exception $e) {
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'error' => 'Unauthorized to delete this machine'
                ], 403);
            }
            
            return response()->json([
                'error' => 'Failed to delete machine'
            ], 404);
        }
    }
}
