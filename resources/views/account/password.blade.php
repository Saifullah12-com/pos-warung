@extends('layouts.app')
@section('title', 'Ganti Password')
@section('content')
<form method="POST" action="{{ route('password.update') }}" class="mx-auto max-w-sm space-y-4 rounded-xl bg-white p-6 shadow-sm">
    @csrf @method('PUT')
    <h1 class="text-lg font-bold">Ganti password</h1>
    <x-input name="current_password" label="Password lama" type="password" required />
    <x-input name="password" label="Password baru (min. 6 karakter)" type="password" required />
    <x-input name="password_confirmation" label="Ulangi password baru" type="password" required />
    <x-btn type="submit">Simpan</x-btn>
</form>
@endsection
