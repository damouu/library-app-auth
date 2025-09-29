<?php

namespace App\Http\Controllers;

use App\Models\User;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * function to allow a user to create an account that will be registered in the database.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
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
        } catch (Exception $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);

    }

    /**
     * logs a user and return a JWT by credentials through Basic auth.
     * 取得したのユーザーのcredentialsをデータベースに確認してから合ってるならJWTを作成して返す。
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
        } catch (Exception $e) {
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

        } catch (Exception $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    /**
     * checks if the given token is still valid or expired by checking it's expired date to actual time.
     * 取得されたのJWTの期限が切られたやらを確認する為にJWTのEXP＿DATEとヨーロッパの現在日時を比べるの関数です。
     * そのJWTはまだ使えるならJWTの内に入ってるのユーザーIDをデータベースに有るかどうか確認してからtrueのリスポンすを返事する。s。
     *
     * @return JsonResponse returns a JsonResponse with a boolean value. if the token is still unexpired returns true.
     * @throws Exception cheks if the user's id present in the token does exist or not in the database.
     */
    public function checkJWT(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $tokenDecoded = base64_decode($token);
        $rere = explode("}", $tokenDecoded);
        $piki = explode("{", $rere[1]);
        $lolo = explode(",", $piki[1]);
        foreach ($lolo as $value) {
            $position = strpos($value, ':');
            $key = (substr($value, 0, $position));
            $value = (substr($value, $position + 1));
            $arrayOfValue[$key] = $value;
        }
        $userID = substr($arrayOfValue['"sub"'], 1, strlen($arrayOfValue['"sub"']) - 2);
        $tokenExpiredDate = new DateTime('@' . $arrayOfValue['"exp"']);
        $today = new DateTime();
        $today->setTimezone(new DateTimeZone('Europe/Paris'));
        $tokenExpiredDate->setTimezone(new DateTimeZone('Europe/Paris'));
        try {
            if ((new User)->findOrFail($userID) && $tokenExpiredDate > $today) {
                return response()->json(['ok' => true]);
            } else {
                return response()->json(['error' => 'invalid token'], 500);
            }
        } catch (Exception $exception) {
            return response()->json(['error' => 'invalid token'], 500);
        }
    }
}
