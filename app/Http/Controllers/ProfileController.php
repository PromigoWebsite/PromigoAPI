<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function fetchProfileById(Request $request){
        $profile = User::find($request->id);
        return response()->json($profile);
    }

    public function editProfileById(Request $request,$id){
        try {
            // dd($request->all());
            $request->validate(
                [
                    'email' => 'required|string',
                    'password' => 'nullable|min:6|regex:/[A-Z]/',
                    'username' => 'required|string',
                    'mobile' => 'required|string',
                    'oldPassword' => 'nullable|required_with:password|string',
                    'profile_picture' => 'nullable',
                ],
            );

            
            // dd($id);
            $user = User::findOrFail($id);
            if($request->password){
                if(Hash::check($request->oldPassword, $user->password) == false)
                abort(401,"Password Lama tidak sesuai");
            }

            if($request->hasFile('profile_picture')){
                $uuid = Str::uuid()->toString();
                $path = Storage::disk('supabase')->putFileAs('/asset/user',$request->profile_picture,$id."_".$uuid);
                $user->update([
                    'profile_picture' => $path,
                ]);
            }

            if ($request->filled('password')) {
                $user->update([
                    'password'=>$request->password
                ]);
            }

            $user->update([
                'username' => $request->username,
                'mobile' => $request->mobile,
                'email' => $request->email,
                'updated_at' => Carbon::now('UTC'),
            ]);
            
            return response()->json([
                'message' => 'Profile updated successfully',
                'profile' => $user
            ], 200);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
