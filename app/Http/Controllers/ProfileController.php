<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function fetchProfileById(Request $request){
        $profile = User::find($request->id);
        return response()->json($profile);
    }

    public function editProfileById(Request $request){
        try {
            $validated = $request->validate([
                'id' => 'required|exists:users,id',
                'username' => 'sometimes|string|max:255',
                'full_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email',
                'mobile' => 'sometimes|string|max:20',
            ]);
            
            $profile = User::findOrFail($request->id);
            
            $updateData = collect($validated)->except(['id'])->toArray();
            
            $profile->update($updateData);
            
            return response()->json([
                'message' => 'Profile updated successfully',
                'profile' => $profile
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
