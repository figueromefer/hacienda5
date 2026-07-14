<?php

namespace App\Http\Requests;

use App\Rules\EmailList;
use Illuminate\Foundation\Http\FormRequest;

class SendReceiptEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage payments') === true;
    }

    public function rules(): array
    {
        return [
            'receipt_to' => ['nullable', 'string', 'max:4000', 'required_with:receipt_cc', new EmailList],
            'receipt_cc' => ['nullable', 'string', 'max:4000', new EmailList],
        ];
    }

    public function attributes(): array
    {
        return [
            'receipt_to' => 'Para',
            'receipt_cc' => 'CC',
        ];
    }
}
