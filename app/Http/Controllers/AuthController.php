<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AuthController extends Controller
{
    public function registerLihat()
    {
        return view('auth.register');
    }

    public function registerSubmit(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal harus 8 karakter.',
        ]);

        $data = new User();
        $data->name = $request->name;
        $data->email = $request->email;
        $data->password = bcrypt($request->password);
        $data->save();
        return redirect()->route('login.lihat')->with('success', 'Registrasi berhasil. Silakan login.');
    }

    public function loginLihat()
    {
        return view('auth.login');
    }

    public function loginSubmit(Request $request)
    {
        $data = User::where('name', $request->name)->first();
        if ($data && password_verify($request->password, $data->password)) {
            auth()->login($data);
            $request->session()->regenerate();
            return redirect()->route('dashboard')->with('success', 'Login berhasil.');
        }

        return redirect()->route('login.lihat')->with('failed', 'Nama pengguna atau password tidak valid.');
    }

    public function logout()
    {
        auth()->logout();
        
        return redirect()->route('login.lihat')->with('success', 'Logout berhasil.');
    }
}
