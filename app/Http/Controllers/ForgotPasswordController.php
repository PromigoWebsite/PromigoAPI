<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller {
    public function sendResetLinkEmail(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Email tidak ditemukan',
                'errors' => $validator->errors()
            ], 422);
        }

        $token = Str::random(6); 

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        try {
            Mail::raw("Kode reset password Anda adalah: {$token}\n\nKode ini berlaku selama 1 jam.\n\nJika Anda tidak meminta reset password, abaikan email ini.", function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Reset Password - Promigo')
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return response()->json([
                'message' => 'Kode reset password telah dikirim ke email Anda'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengirim email: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'message' => 'Token tidak ditemukan'
            ], 404);
        }

        if (!Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'message' => 'Token tidak valid'
            ], 400);
        }

        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            return response()->json([
                'message' => 'Token sudah expired'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Password berhasil direset'
        ], 200);
    }
}
