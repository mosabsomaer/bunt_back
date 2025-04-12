<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Machine;
use App\Types\OrderStatusEnum;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\MachineService;
class OrderService 
{
    public function __construct(
        protected Order $order,
        protected Machine $machine
    ) {}

    public function getOrders(): LengthAwarePaginator
    {
        return $this->order->paginate();
    }

    public function getOrderByOrderId(string $orderId): Order
    {
        $order = $this->order->where('order_id', $orderId)->first();
        
        if (!$order) {
            throw new ModelNotFoundException('Order not found');
        }

        return $order;
    }

    public function createOrder(): Order
    {
        return $this->order->create([
            'status' => OrderStatusEnum::PENDING->value
        ]);
    }

    public function updateOrder(Order $order, array $data): Order
    {
        $order->update([
            'number_pages' => $data['number_pages']
        ]);

        return $order->fresh();
    }

    public function updateOrderStatus(Order $order, OrderStatusEnum $status): Order
    {
        $order->update(['status' => $status->value]);
        
        if ($status === OrderStatusEnum::COMPLETED) {
            $this->completeOrder($order);
        }

        return $order->fresh();
    }

    public function deleteOrder(Order $order): void
    {        
        if ($order->files) {
            $files = $order->files;
            // Delete physical files first
            foreach ($files as $file) {
                Storage::delete($file->path);
                $file->delete();
            }
        }
        
        // Then delete the order
        $order->delete();
    }

    public function completeOrder(Order $order): void 
    {
        $files = $order->files;
        if (!$files) {
            return;
        }

        $totalPrice = $this->calculateTotalPrice($files);
        $this->deleteOrderFiles($files);
        $this->updateMachineStatus($order, $totalPrice);
    }

    public function getOrderStatistics(Order $order): array
    {
        if (!$order->files) {
            return [
                'total_price' => 0,
                'files_count' => 0
            ];
        }

        $files = $order->files;
        $filesCount = $files->count();
        return [
            'total_price' => $filesCount > 0 ? $this->calculateTotalPrice($files) : 0,
            'files_count' => $filesCount
        ];
    }

    protected function calculateTotalPrice(Collection $files): float
    {
        return $files->sum('price');
    }

    protected function deleteOrderFiles(Collection $files): void
    {
        if ($files->isEmpty()) {
            return;
        }

        foreach ($files as $file) {
            Storage::delete($file->path);
        }
    }


}

?>
