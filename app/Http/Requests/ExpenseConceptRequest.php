<?php

namespace App\Http\Requests;

use App\Models\ExpenseConcept;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseConceptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage expense concepts') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeSpaces($this->input('name')),
            'description' => $this->normalizeNullableText($this->input('description')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $query = ExpenseConcept::query()
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($value)]);

                    if ($expenseConcept = $this->route('expense_concept')) {
                        $query->whereKeyNot($expenseConcept->getKey());
                    }

                    if ($query->exists()) {
                        $fail('Ya existe un concepto de gasto con ese nombre, incluso entre los archivados.');
                    }
                },
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
        ];
    }

    private function normalizeSpaces(mixed $value): mixed
    {
        return is_string($value) ? preg_replace('/\s+/u', ' ', trim($value)) : $value;
    }

    private function normalizeNullableText(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = preg_replace('/[\t ]+/u', ' ', trim($value));

        return $value === '' ? null : $value;
    }
}
