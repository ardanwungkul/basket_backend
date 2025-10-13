<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Register a user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'parent'
        ]);

        $guardian = new Guardian();
        $guardian->name = $request->name;
        $guardian->email = $request->email;
        $guardian->user_id = $user->id;
        $guardian->save();

        $token = JWTAuth::fromUser($user);

        return response()->json(['status' => 201, 'token' => $token, 'user' => $user], 201);
    }

    /**
     * Login a user and create a token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);


        $loginInput = $request->input('username');
        $password = $request->input('password');

        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $loginField => $loginInput,
            'password' => $password
        ];
        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(
                    [
                        'status' => 422,
                        'errors' => [['Unauthorized - Invalid Credentials']]
                    ],
                    422
                );
            }
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'error' => 'Could not create token',
                'message' => $e->getMessage()
            ], 500);
        }

        $user = User::find(Auth::user()->id);

        return response()->json([
            'status' => 201,
            'token' => $token,
            'user' => $user
        ]);
    }

    public function getAuthenticatedUser()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'User not found'], 404);
            }
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Token is invalid'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token absent'], 401);
        }

        return response()->json(compact('user'));
    }
}
