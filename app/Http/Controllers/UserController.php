<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function editUser(Request $request){
        $request->validate(
            [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
                'username' => 'required|string',
                'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:users,mobile',
            ],
            [
                'email.required' => 'Email harus diisi',
                'email.email' => 'Format email tidak valid',
                'email.unique' => 'Email sudah terdaftar',


                'password.required' => 'Password harus diisi',
                'password.min' => 'Password minimal 6 karakter',

                'username.required' => 'Username harus diisi',

                'mobile.required' => 'Nomor telepon harus diisi',
                'mobile.regex' => 'Format nomor telepon tidak valid',
                'mobile.min' => 'Nomor telepon minimal 10 digit',
                'mobile.unique' => 'Nomor telepon sudah terdaftar',
            ]
        );

        $user = [
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'mobile' => $request->mobile,
            ''
        ]
    }
}
