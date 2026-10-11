<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadRepairRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'status' => ['nullable', Rule::in(['open', 'in_progress', 'closed'])],
            'search' => ['nullable', 'string', 'max:2000'],
            'sort' => ['nullable', Rule::in(['date', 'priority'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
