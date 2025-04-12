<?php

namespace App\Http\Requests\Order;

use App\Types\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderStatusRequest extends FormRequest
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
            'status' => ['required', new Enum(OrderStatusEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'The status field is required.',
            'status.Illuminate\Validation\Rules\Enum' => 'Invalid status. Allowed values are: pending, completed, cancelled'
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Validation\ValidationException($validator, response()->json([
            'status' => 'error',
            'message' => $validator->errors()->first()
        ], 422));
    }
}
