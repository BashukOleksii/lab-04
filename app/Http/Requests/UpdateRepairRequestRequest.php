<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRepairRequestRequest extends FormRequest
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
            'department_id' => [
                'required',
                'integer',
                'exists:departments,id'
            ],
            'subject' => [
                'required',
                'string',
                'max:200'
            ],
            'priority'=>[
                'required',
                'integer',
                'between:1,3'
            ],
            'status'=>[
                'required',
                 Rule::in(['open','closed','in_progress'])
            ],
            'urgent'=>[
                'required',
                'boolean'
            ],
            'cost'=>[
                'nullable',
                'require_if:status,closed',
                'numeric',
                'min:0'
            ],
            'completed_at'=>[
                'nullable',
                'require_if:status,closed',
                'date',
                'after_or_equal:reported_at'
            ]
        ];
    }
}
