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
        $data = new User();
        $data->name = $request->name;
        $data->email = $request->email;
        $data->password = bcrypt($request->password);
        $data->save();
        return redirect()->route('login.lihat')->with('success', 'Registration successful. Please log in.');
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
            return redirect()->route('dashboard')->with('success', 'Login successful.');
        }

        return redirect()->route('login.lihat')->with('failed', 'Invalid username or password.');
    }

    public function logout()
    {
        auth()->logout();
        
        return redirect()->route('login.lihat')->with('success', 'Logged out successfully.');
    }
}
