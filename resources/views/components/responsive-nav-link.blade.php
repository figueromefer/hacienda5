@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-4 pe-4 py-3 border-l-4 border-brand-gold text-start text-base font-semibold text-brand-gold bg-white/10 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-gold transition duration-150 ease-in-out'
            : 'block w-full ps-4 pe-4 py-3 border-l-4 border-transparent text-start text-base font-medium text-white/85 hover:text-brand-gold hover:bg-white/10 hover:border-brand-gold/50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-gold transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
