<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepairRequestRequest extends FormRequest
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
                'required_if:status,closed',
                'numeric',
                'min:0'
            ],
            'reported_at' =>[
                'required',
                'date'
            ],
            'completed_at'=>[
                'nullable',
                'required_if:status,closed',
                'date',
                'after_or_equal:reported_at'
            ],
            'initial_comment' =>[
                'nullable',
                'string',
                'max:2000'
            ]
        ];
    }
}
