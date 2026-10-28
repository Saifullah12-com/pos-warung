<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Kelola akun KASIR (akun Admin dibuat developer lewat seeder)
class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::orderBy('role')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        User::create($data + ['role' => 'kasir', 'is_active' => true]);

        return back()->with('success', 'Akun kasir ditambahkan.');
    }

    public function toggle(User $user)
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'Akun Admin tidak dapat dinonaktifkan.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', 'Status akun diperbarui.');
    }

    public function resetPassword(User $user)
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'Password Admin diganti lewat menu Ganti Password.']);
        }

        $new = Str::password(8, symbols: false);
        $user->update(['password' => $new]);

        return back()->with('new_password', ['username' => $user->username, 'password' => $new]);
    }
}
