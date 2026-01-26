<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Services\AuthService;
use Exception;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    /**
     * ユーザーがアカウントを作成するように関数です。入力されたデータがデーターベースに登録されます。
     * 入力されたのデータが適切な値である場合にJWTのペイロード部の内にはユーザーIDやstudentIDカード番号や有効期限を指定されるデータがencodeされてJWTを作成されてJSON形成のレスポンスにに返事されます。
     * バリデーションで入力されたが適切な値ではない場合はバリデーションの例外のエーラを発生されます。
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws Exception
     * @author damouu
     */
    public function register(UserRequest $userRequest): JsonResponse
    {

        $validatedData = $userRequest->validated();

        $response = $this->authService->register($validatedData);

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => $response['expires_in'],
            'expires_at' => $response['expires_at'],
            'access_token' => $response['jwt'],
        ], 201);
    }

    /**
     * logs a user and return a JWT by credentials through Basic auth.
     * 取得したのユーザーのcredentialsをデータベースに確認してから合ってるならJWTは作成されてレスポンスで返されます。
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws Exception
     */
    public function login(Request $request): JsonResponse
    {
        $email = $request->getUser();
        $password = $request->getPassword();

        if (!$email || !$password) {
            return response()->json(['message' => 'Missing credentials'], 401);
        }

        $response = $this->authService->login($email, $password);

        return response()->json([
            'token_type' => 'Bearer',
            'expires_in' => $response['expires_in'],
            'expires_at' => $response['expires_at'],
            'access_token' => $response['jwt'],
        ]);
    }


    /**
     * この関数によっては取得したのJWTにユーザーID部でログインされているのみのユーザーの個人データをデーターベースに取得されてレスポンスに返事されます。
     * JWTの有効期限されているならExpiredExceptionで例外のエーラを発生されます。
     *
     * @param Request $request
     * @return array
     * @throws ExpiredException
     */
    public function getUserProfile(Request $request): array
    {
        $token = $request->bearerToken();

        return $this->authService->getUserProfile($token);
    }


    /**
     * ログインされているのユーザーを削除するの関数です。
     * 削除と言うよりIlluminateのORMライブラリーでユーザーテーブルのdeleted_at欄に現実の日付を追加されて保存されます。
     * そうすれば、データーベースにユーザーのデータがされていたままだがIlluminateのORMでユーザーを検索されたら削除されているユーザーが出力されません。
     *
     * @param Request $request
     * @return Response
     */
    public function deleteUser(Request $request): Response
    {
        $token = $request->bearerToken();

        $response = $this->authService->deleteUser($token);

        return response(null, $response);

    }
}
