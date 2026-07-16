@props([
    'disabled' => false,
    'name',
    'value' => '',
])

<input
    type="text"
    name="{{ $name }}"
    value="{{ $value }}"
    inputmode="decimal"
    autocomplete="off"
    x-data="moneyInput(@js((string) $value))"
    x-model="display"
    x-init="init()"
    x-on:focus="unformat()"
    x-on:blur="format()"
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'w-full rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500']) }}
>

