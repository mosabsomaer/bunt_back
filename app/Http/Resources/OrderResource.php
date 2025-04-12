<?php

namespace App\Http\Resources;

use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    protected OrderService $orderService;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->orderService = app(OrderService::class);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $statistics = $this->orderService->getOrderStatistics($this->resource);

        return [
            'order_id' => $this->order_id,
            'status' => $this->status,
            'number_pages' => $this->number_pages,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'total_price' => $statistics['total_price'],
            'files_count' => $statistics['files_count'],
            'files' => $this->whenLoaded('files'),
        ];
    }
}
