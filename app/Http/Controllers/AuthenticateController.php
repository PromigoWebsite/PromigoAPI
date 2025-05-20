<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthenticateController extends Controller
{
    private function validateUser($request){
        try {
            $user = User::where('email', $request->email)->first();
            if(!$user){
                abort(401,"Email tidak ditemukan");
            }
            
            $checkPassword = Hash::check($request->password, $user->password);;
            if($checkPassword){
                return $user;
            }

            abort(401,"Password tidak sesuai");
        } catch (Exception $e) {
            throw $e;
        }
        
    }
    
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = $this->validateUser($request);
            $user->update(['updated_at' => now()]);
            
            $expiration  = $request->rememberMe;
            $token = $user->createToken('auth_token',['*'], $expiration ? now()->addDays(30) : now()->addDays(7))->plainTextToken;
            $cookie = cookie('promigo_token', $token, $expiration ? 60*24*30: config('session.lifetime'));
            
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

    public function user(){
        $user =  Auth::user();
        return response()->json([
            'user' => $user,
        ]);
    }

    public function logout(Request $request){
        if (Auth::user()) {
            Auth::user()->tokens()->delete();
        }

        $request->session()->invalidate();
        $cookie = cookie()->forget('promigo_token');

        return response()->json([
            'message' => 'Logged out successfully'
        ])->withCookie($cookie);
    }

    public function register(Request $request){
        try {
            $request->validate([
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
                'username' => 'required|string',
                'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:users,mobile',
            ]);

            $user = [
                'email' => $request->email,
                'password' => Hash::make($request->password), 
                'username' => $request->username,
                'mobile' => $request->mobile,
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
