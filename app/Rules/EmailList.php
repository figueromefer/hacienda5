<?php

namespace App\Rules;

use App\Support\ReceiptRecipients;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (ReceiptRecipients::parse((string) $value) as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $fail('El campo :attribute contiene un correo no válido: '.$email.'.');
            }
        }
    }
}
