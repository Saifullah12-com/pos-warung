@extends('layouts.app')
@section('title', 'Akun')
@section('content')
    <h1 class="mb-4 text-lg font-bold">Akun pengguna</h1>

    @if (session('new_password'))
        <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Password baru untuk <b>{{ session('new_password')['username'] }}</b>:
            <code class="rounded bg-white px-2 py-0.5 text-base font-bold">{{ session('new_password')['password'] }}</code>
            <div class="text-xs">Catat sekarang dan berikan ke kasir. Password ini tidak akan ditampilkan lagi.</div>
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}"
        class="mb-6 grid max-w-3xl items-end gap-3 rounded-xl bg-white p-4 shadow-sm sm:grid-cols-4">
        @csrf
        <x-input name="name" label="Nama kasir" required />
        <x-input name="username" label="Username" required />
        <x-input name="password" label="Password awal" type="password" required />
        <x-btn type="submit">Tambah kasir</x-btn>
    </form>

    <div class="max-w-3xl overflow-x-auto rounded-xl bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="p-3">Nama</th>
                    <th class="p-3">Username</th>
                    <th class="p-3">Peran</th>
                    <th class="p-3">Status</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($users as $u)
                    <tr class="{{ $u->is_active ? '' : 'text-slate-400' }}">
                        <td class="p-3">{{ $u->name }}</td>
                        <td class="p-3">{{ $u->username }}</td>
                        <td class="p-3">{{ $u->role }}</td>
                        <td class="p-3">{{ $u->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                        <td class="space-x-3 whitespace-nowrap p-3 text-right">
                            @unless ($u->isAdmin())
                                <form method="POST" action="{{ route('users.toggle', $u) }}" class="inline">@csrf
                                    @method('PATCH')
                                    <button
                                        class="text-slate-700 hover:underline">{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                                <form method="POST" action="{{ route('users.reset', $u) }}" class="inline"
                                    onsubmit="return confirm('Reset password {{ $u->username }}?')">@csrf @method('PATCH')
                                    <button class="text-blue-600 hover:underline">Reset password</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
