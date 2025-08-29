<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function registerKaryawan(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email:rfc,dns|unique:users,email',
            'password'              => 'required|string|min:8',
        ]);

        $data['email'] = strtolower($data['email']);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'role'     => 'karyawan',
        ]);

        $token = $user->createToken('pat')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        ], 201);
    }

    public function registerAdmin(Request $request)
{
    $data = $request->validate([
        'name'                  => 'required|string|max:100',
        'email'                 => 'required|email|unique:users,email',
        'password'              => 'required|string|min:8',
    ]);

    $user = User::create([
        'name'     => $data['name'],
        'email'    => strtolower($data['email']),
        'password' => Hash::make($data['password']),
        'role'     => 'admin',
    ]);

    return response()->json([
        'message' => 'Admin registered',
        'user'    => ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'role'=>$user->role],
    ], 201);
}


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email:rfc,dns',
            'password' => 'required|string',
        ]);

        $email = strtolower($credentials['email']);

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('pat')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
            'role'  => $request->user()->role,
        ]);
    }

    public function logout(Request $request)
{
    $request->user()->tokens()->delete();
    return response()->json(['message' => 'Logged out from all devices']);
}


}