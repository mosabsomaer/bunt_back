<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFileRequest extends FormRequest
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
            'color_mode' => ['nullable', 'boolean'],
            'file_name' => ['string'],
            'JobID' => ['string'],
            'copies' => ['nullable', 'integer', 'min:1'],
            'order_id' => ['string', 'min:6', 'max:6', 'exists:orders,order_id'],
            'PageCount' => ['nullable', 'integer', 'min:1'],
            'path' => ['string'],
        ];
    }
}
