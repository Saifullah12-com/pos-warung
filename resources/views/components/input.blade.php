@props(['name', 'label', 'type' => 'text', 'value' => null])
<label class="block text-sm">
    <span class="mb-1 block font-medium text-slate-700">{{ $label }}</span>
    <input type="{{ $type }}" name="{{ $name }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        {{ $attributes->merge(['class' => 'w-full rounded-lg border bg-white px-3 py-2 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600 ' . ($errors->has($name) ? 'border-red-500' : 'border-slate-300')]) }}>
</label>
