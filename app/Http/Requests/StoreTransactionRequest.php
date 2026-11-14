<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
            'account_id' => ['required', 'exists:accounts,id'],
            'destination_account_id' => [
                'nullable',
                'required_if:type,transfer',
                'different:account_id',
                'exists:accounts,id',
            ],
            'category_id' => ['nullable', 'required_unless:type,transfer', 'exists:categories,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'fee' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'payee_payer' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'string', 'in:bank_transfer,credit_card,debit_card,cash,upi,paypal,stripe,check,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_interval' => [
                'nullable',
                'required_if:is_recurring,1',
                Rule::in(['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly']),
            ],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'], // 5MB max
        ];
    }
}

