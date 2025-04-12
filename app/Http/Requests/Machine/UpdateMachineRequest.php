<?php

namespace App\Http\Requests\Machine;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Types\MachineStatusEnum;
class UpdateMachineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['string'],
            'paper' => ['integer'],
            'coins' => ['integer'],
            'ink' => ['integer'],
            'status' => ['string', Rule::in(MachineStatusEnum::values())],
        ];
    }
}
