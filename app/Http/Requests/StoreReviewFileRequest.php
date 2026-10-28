<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:2560',
            ],
        ];
    }
}
