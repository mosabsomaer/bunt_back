<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\Storage;
use App\Models\File;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DailyTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:daily-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $yesterday = Carbon::now()->subDay();
        $yesterdayOrders = Order::whereDate('created_at', $yesterday)->get();
        foreach ($yesterdayOrders as $order) {
            $files = File::where('order_id', $order->order_id)->get();
            foreach ($files as $file) {
                $filePath = storage_path('app/' . $file->path);
                Storage::delete($filePath);
            }
        }
    }
}
