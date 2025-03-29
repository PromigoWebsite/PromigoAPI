<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $profiles = Profile::all();
        return response()->json([
            'success' => true,
            'data' => $profiles
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:profiles,username',
            'fullname' => 'required|string',
            'role' => 'required|string',
            'email' => 'required|email|unique:profiles,email',
            'mobile' => 'required|string'
        ]);

        $profile = Profile::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $profile
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $profile = Profile::find($id);

        if(!$profile)
        {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 400);
        }

        else
        {
            return response()->json([
                'success' => true,
                'data' => $profile
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $profile = Profile::find($id);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 400);
        }

        $request->validate([
            'username' => 'sometimes|string|unique:profiles,username,'.$id,
            'fullname' => 'sometimes|string',
            'role' => 'sometimes|string',
            'email' => 'sometimes|email|unique:profiles,email,'.$id,
            'mobile' => 'sometimes|string'
        ]);

        $profile->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $profile = Profile::find($id);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 400);
        }

        $profile->delete();

        return response()->json([
            'success' => true,
            'message' => 'Profile deleted successfully'
        ]);
    }
}
