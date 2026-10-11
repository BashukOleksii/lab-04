<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadRepairRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'department_id' =>[
                'nullable',
                'integer',
                'exists:departments,id'
            ],

            'status' => [
                'nullable',
                Rule::in(['open','closed','in_progress'])
            ],

            'search' => [
                'nullable',
                'string',
                'max:2000'
            ],

            'direction' => [
                'nullable',
                Rule::in(['asc','desc'])
            ],
        ];
    }
}
