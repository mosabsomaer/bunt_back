<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyPrinterToken
{
    public function handle(Request $request, Closure $next)
    {
        // Replace 'your-printer-token-here' with your actual printer token
        $printerToken = env('BUNT_MACHINE_API_KEY');
        $totalPrice = $request->header('total_price');
        $filename=$request->header('Content-Disposition');
        // Check if the API token matches the printer token
        if ($request->header('Authorization') !== $printerToken) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $request->merge(['total_price' => $totalPrice, 'Content-Disposition'=>$filename]);
        return $next($request);
    }
}
