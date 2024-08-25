<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Order;
use App\Models\File;
use Illuminate\Support\Facades\DB;
use App\Models\Machine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Throwable;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Handle the error response.
     *
     * @param \Exception $e
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */


    protected function handleError(Throwable $e, $statusCode = 500)
    {
        if ($e instanceof ModelNotFoundException) {
            $statusCode = 404;
            $error = 'Resource not found.';
        } elseif ($e instanceof ValidationException) {
            $statusCode = 422;
            $error = $e->errors();
        } elseif ($e instanceof AuthenticationException) {
            $statusCode = 401;
            $error = 'Not authorized.';
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
            $admins = Admin::all();
            return response()->json([
                'data' => $admins
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $input = $request->validate([
                'username' => ['required', 'unique:admins,username', 'string'],
                'password' => ['required', 'min:8']
            ]);

            $admin = Admin::create($input);

            $token = $admin->createToken("API TOKEN");

            if (!$token) {
                throw new \Exception('Failed to create API token.');
            }

            return response()->json([
                'data' => 'created',
                'token' => $token->plainTextToken
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation error',
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Database error',

            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login The User
     * @param Request $request
     * @return User
     */
    public function loginAdmin(Request $request)
    {
        try {
            $validateAdmin = Validator::make(
                $request->all(),
                [
                    'username' => 'required',
                    'password' => 'required'
                ]
            );

            if ($validateAdmin->fails()) {
                $errors = '';
                foreach ($validateAdmin->errors()->all() as $error) {
                    $errors .= $error . ' ';
                }
                return response()->json([
                    'status' => false,
                    'message' =>  $errors
                ], 401);
            }

            if (!Auth::guard('admin')->attempt($request->only(['username', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'username & Password does not match with our record.',
                ], 401);
            }

            $admin = Admin::where('username', $request->username)->first();

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $admin->createToken("API TOKEN")->plainTextToken
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }



    public function logoutadmin(Request $request)
    {
        try {
            $token = $request->input('token');
            $tokenWithoutId = substr($token, strpos($token, '|') + 1);
            $deletedRows = DB::table('personal_access_tokens')
                ->where('token', hash('sha256', $tokenWithoutId))
                ->delete();

            if ($deletedRows > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'logged out successfully',
                    'token' => $tokenWithoutId
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'error logging out',
                    'token' => $tokenWithoutId
                ], 401);
            }
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'error logging out',
                'error' => $e->getMessage(),
                'token' => $tokenWithoutId
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $admin = Admin::findOrFail($id);
            return response()->json([
                'data' => $admin
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
            $admin = Admin::findOrFail($id);

            $input = $request->validate([
                'username' => [Rule::unique('admins', 'username')->ignore($admin), 'string'],
                'password' => ['min:8']
            ]);

            $admin->update($input);

            return response()->json([
                'data' => 'updated',
                'input' => $input,
                'request' => $request->request
            ], 200);
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
            $admin = Admin::findOrFail($id);
            $admin->delete();
            return response()->json([
                'data' => 'Admin Deleted'
            ]);
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
    }



    public function statistics()
    {
        try {
            $today = Carbon::today();
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $totalTodayPrice = 0;
            $totalMonthPrice = 0;
            $totalPages = 0;

            $todayOrders = Order::whereDate('updated_at', $today)
                ->where('status', 'Completed')
                ->get();


            foreach ($todayOrders as $order) {
                $files = File::where('order_id', $order->order_id)->get();
                $totalTodayPrice += $files->sum('price');
            }



            $monthOrders = Order::whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->where('status', 'Completed')
                ->get();

            $countorders = count($monthOrders);

            foreach ($monthOrders as $order) {
                $files = File::where('order_id', $order->order_id)->get();
                $totalMonthPrice += $files->sum('price');
                $totalPages += $order->number_pages;
            }
            $averagePages = $countorders > 0 ? floor($totalPages / $countorders) : 0;
            $machine = Machine::findOrFail(2);
            $lastPing = Carbon::parse($machine->last_ping);

            $machine['last_ping']= Carbon::now()->diffInSeconds($lastPing);

            return response()->json([
                'Today_sales' => $totalTodayPrice,
                'Monthly_sales' => $totalMonthPrice,
                'count_orders' => $countorders,
                'Average_pages_per_order' => $averagePages,
                'status' => $machine,
                'message' => 'Statistics fetched successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while fetching statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }
};
