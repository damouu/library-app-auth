<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'avatar_img_URL' => $request->avatar_img_URL,
            'studentCardUUID' => $request->studentCardUUID,
            'last_logged_in_at' => null,
            'deleted_at' => null,
            'updated_at' => null,
            'password' => Hash::make($request->password),
        ]);
        try {
            $token = Auth::login($user);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);

    }

    /**
     * logs a user and return a JWT by  credentials through Basic auth.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $authorization = base64_decode(substr($request->header('Authorization'), 6));
        $position = strpos($authorization, ':');
        $password = substr($authorization, $position + 1);
        $email = substr($authorization, 0, $position);
        $user = (new User)->where("email", $email)->first();
        try {
            if (!Hash::check($password, $user->password)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        try {
            $token = Auth::login($user);
            Auth::check();
            $user->last_logged_in_at = now();
            $user->save();
            return response()->json([
                'token' => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }
}
