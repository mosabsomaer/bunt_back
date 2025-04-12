<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
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
            'JobID' => ['required', 'string'],
            'copies' => ['required', 'integer', 'min:1'],
            'color_mode' => ['required', 'boolean'],
            'order_id' => ['required', 'string', 'min:6', 'max:6', 'exists:orders,order_id'],
        ];
    }
}
