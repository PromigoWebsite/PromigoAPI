<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthenticateController extends Controller {
    private function validateUser($request) {
        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                abort(401, "Email tidak ditemukan");
            }

            $checkPassword = Hash::check($request->password, $user->password);
            if ($checkPassword) {
                return $user;
            }

            abort(401, "Password tidak sesuai");
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function login(Request $request) {
        try {
            $request->validate(
                [
                    'email' => 'required|email',
                    'password' => 'required',
                ],
                [
                    'email.required' => 'Email harus diisi',
                    'email.email' => 'Format email tidak sesuai',
                    'password.required' => 'Password harus diisi',
                ]
            );

            $user = $this->validateUser($request);
            $user->update(['updated_at' => now()]);

            $expiration  = $request->rememberMe;
            $token = $user->createToken('auth_token', ['*'], $expiration ? now()->addDays(30) : now()->addDays(7))->plainTextToken;
            $cookie = cookie('promigo_token', $token, $expiration ? 60 * 24 * 30 : config('session.lifetime'));

            Auth::loginUsingId($user->id);

            return response()->json([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'message' => 'Login successful'
            ])->withCookie($cookie);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function user() {
        try {
            $user = Auth::user();
            if ($user) {
                $userData = User::join('roles', 'roles.id', '=', 'users.role_id')
                    ->where('users.id', $user->id)
                    ->select(
                        'users.*',
                        'roles.name as role'
                    );

                $tempUser = $userData->first();

                if ($tempUser && $tempUser->role === 'Seller') {
                    $user = User::join('roles', 'roles.id', '=', 'users.role_id')
                        ->join('brands', 'brands.user_id', '=', 'users.id')
                        ->where('users.id', $user->id)
                        ->select(
                            'users.*',
                            'roles.name as role',
                            'brands.id as brand_id'
                        )
                        ->first();
                } else {
                    $user = $tempUser;
                }
            }

            return response()->json([
                'user' => $user,
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function logout(Request $request) {
        if (Auth::user()) {
            Auth::user()->tokens()->delete();
        }
        if ($request->hasSession()) {
            $request->session()->invalidate();
        }
        $cookie = cookie()->forget('promigo_token');

        return response()->json([
            'message' => 'Logged out successfully'
        ])->withCookie($cookie);
    }



    public function register(Request $request) {
        try {
            $request->validate(
                [
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|min:6|regex:/[A-Z]/',
                    'username' => 'required|string',
                    'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:users,mobile',
                ],
                [
                    'email.required' => 'Email harus diisi',
                    'email.email' => 'Format email tidak valid',
                    'email.unique' => 'Email sudah terdaftar',


                    'password.required' => 'Password harus diisi',
                    'password.min' => 'Password minimal 6 karakter',
                    'password.regex' => 'Password harus memiliki minimal 1 huruf kapital',

                    'username.required' => 'Username harus diisi',

                    'mobile.required' => 'Nomor telepon harus diisi',
                    'mobile.regex' => 'Format nomor telepon tidak valid',
                    'mobile.min' => 'Nomor telepon minimal 10 digit',
                    'mobile.unique' => 'Nomor telepon sudah terdaftar',
                ]
            );

            $role = Role::where('name', 'User')->value('id');


            $user = [
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'username' => $request->username,
                'mobile' => $request->mobile,
                'role_id' => $role,
            ];

            User::create($user);

            $loginRequest = new Request([
                'email' => $request->email,
                'password' => $request->password,
                'rememberMe' => false
            ]);

            return $this->login($loginRequest);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
