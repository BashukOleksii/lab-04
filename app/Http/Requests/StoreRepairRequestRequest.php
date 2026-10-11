<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'subject' => ['required', 'string', 'max:200'],
            'priority' => ['required', 'integer', 'between:1,3'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'closed'])],
            'urgent' => ['required', 'boolean'],
            'reported_at' => ['required', 'date'],
            'cost' => [
                'nullable',
                'required_if:status,closed',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'completed_at' => [
                'nullable',
                'required_if:status,closed',
                'date',
                'after_or_equal:reported_at',
            ],
            'initial_comment' => ['nullable', 'string', 'max:2000'],
            'defect_file' => ['nullable', 'file', 'mimes:jpg,jpeg,pdf', 'max:2560'],
        ];
    }
}
