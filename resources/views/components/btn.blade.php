@props(['variant' => 'primary', 'href' => null])
@php
    $styles = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-70 disabled:cursor-not-allowed disabled:opacity-40',
        'secondary' => 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50',
        'danger' => 'bg-red-700 text-white hover:bg-red-800',
    ];
    $class =
        'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition ' .
        ($styles[$variant] ?? $styles['primary']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $class]) }}>{{ $slot }}</button>
@endif
