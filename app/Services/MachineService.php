<?php

namespace App\Services;

use App\Interfaces\MachineServiceInterface;
use App\Models\Machine;
use App\Models\Order;
use Carbon\Carbon;

class MachineService implements MachineServiceInterface
{
    public function __construct(
        protected Machine $machine
    ) {}

    public function getAllMachines()
    {
        return $this->machine->all();
    }

    public function createMachine(array $data)
    {
        return $this->machine->create($data);
    }

    public function getMachine(string $id)
    {
        return $this->machine->findOrFail($id);
    }

    public function updateMachine(string $id, array $data): Machine
    {
        $machine = $this->machine->findOrFail($id);
        $data['last_ping'] = Carbon::now()->format('Y-m-d H:i:s');
        $machine->update($data);
        return $machine;
    }

    public function deleteMachine(string $id): void
    {
        $machine = $this->machine->findOrFail($id);
        $machine->delete();
    }

    public function updateMachineStatus(Order $order, float $totalPrice): void
    {
        //TODO: this can be more dynamic than hardcoding the machine id
        $machine = $this->machine->findOrFail(2);
        
        $inkUsagePerPage = 0.05; // 100 / 2000  ink % usage per page
        $inkUsage = $order->number_pages * $inkUsagePerPage;

        $machine->update([
            'paper' => $machine->paper - $order->number_pages,
            'coins' => $machine->coins + $totalPrice,
            'ink' => $machine->ink - $inkUsage
        ]);
    }
} 
