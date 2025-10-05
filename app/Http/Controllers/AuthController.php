<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * ユーザーがアカウントを作成するように関数です。入力されたデータがデーターベースに登録されます。
     * 入力されたのデータが適切な値である場合にJWTのペイロード部の内にはユーザーIDやstudentIDカード番号や有効期限を指定されるデータがencodeされてJWTを作成されてJSON形成のレスポンスにに返事されます。
     * バリデーションで入力されたが適切な値ではない場合はバリデーションの例外のエーラを発生されます。
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @author damouu
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->first(), 400);
        }

        $emailValid = $validator->valid()["email"];
        $emailCrop = strpos($emailValid, "@");
        $avatarImgUrl = $validator->valid()["name"] . "+" . (substr($emailValid, 0, $emailCrop));

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'avatar_img_URL' => "https://avatar.iran.liara.run/username?username=" . $avatarImgUrl,
            'studentCardUUID' => $request->studentCardUUID,
            'last_logged_in_at' => null,
            'deleted_at' => null,
            'updated_at' => null,
            'password' => Hash::make($request->password),
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);

        } else {
            $key = 'example_key';
            $payload = [
                'iss' => 'http://example.org',
                'aud' => 'http://example.com',
                'nbf' => 1357000000,
                'iat' => time(),
                'exp' => time() + 3600,
                'user_id' => $user->id,
                'user_studentCardUUID' => $user->studentCardUUID
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');
        }
        return response()->json([
            'message' => true,
            'token' => $jwt,
        ], 201);
    }

    /**
     * logs a user and return a JWT by credentials through Basic auth.
     * 取得したのユーザーのcredentialsをデータベースに確認してから合ってるならJWTを作成して返す。
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $authorization = base64_decode(substr($request->header('Authorization'), 6));
        $position = strpos($authorization, ':');
        $password = substr($authorization, $position + 1);
        $email = substr($authorization, 0, $position);
        $user = User::where("email", $email)->firstOrFail();

        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->last_logged_in_at = now();
        $user->save();
        $key = 'example_key';
        $payload = [
            'iss' => 'http://example.org',
            'aud' => 'http://example.com',
            'nbf' => 1357000000,
            'iat' => time(),
            'exp' => time() + 3600,
            'user_id' => $user->id,
            'user_studentCardUUID' => $user->studentCardUUID
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');

        return response()->json([
            'message' => true,
            'token' => $jwt,
        ]);
    }

    /**
     * checks if the given token is still valid or expired by checking it's expired date to actual time.
     * 取得されたのJWTの期限が切られたやらを確認する為にJWTのEXP＿DATEとヨーロッパの現在日時を比べるの関数です。
     * そのJWTはまだ使えるならJWTの内に入ってるのユーザーIDをデータベースに有るかどうか確認してからtrueのリスポンすを返事する。s。
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function checkJWT(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
        $key = 'example_key';
        $message = '';
        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            if ((new User)->findOrFail($decoded->user_id)) {
                return response()->json(['message' => true]);
            }
        } catch (ExpiredException) {
            $message = ['message' => false];
        }
        return response()->json($message, 400);
    }
}
