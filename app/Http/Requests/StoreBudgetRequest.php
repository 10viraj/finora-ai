<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'spending_limit' => ['nullable', 'numeric', 'min:0.01'],
            'period' => ['required', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'month_year' => ['nullable', 'string', 'max:7'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'alert_percentage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'alert_threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'notify_on_exceed' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

