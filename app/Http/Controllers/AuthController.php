<?php

namespace App\Http\Controllers;

use App\DTO\LoginRequestDTO;
use App\DTO\UserProfileDTO;
use App\Http\Requests\UserRequest;
use App\Services\AuthService;
use App\Services\GetUserProfile;
use App\Services\LoginUserService;
use App\Services\RegisterUserService;
use Exception;
use Firebase\JWT\ExpiredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function __construct(
        protected RegisterUserService $registerUserService,
        protected LoginUserService    $loginUserService,
        protected GetUserProfile      $getUserProfile,
        protected AuthService         $authService,
    )
    {
    }

    /**
     * ユーザーがアカウントを作成するように関数です。入力されたデータがデーターベースに登録されます。
     * 入力されたのデータが適切な値である場合にJWTのペイロード部の内にはユーザーIDやstudentIDカード番号や有効期限を指定されるデータがencodeされてJWTを作成されてJSON形成のレスポンスにに返事されます。
     * バリデーションで入力されたが適切な値ではない場合はバリデーションの例外のエーラを発生されます。
     *
     * @param UserRequest $userRequest
     * @return JsonResponse
     * @throws Throwable
     * @author damouu
     */
    public function register(UserRequest $userRequest): JsonResponse
    {
        $validatedData = $userRequest->validated();
        $response = $this->registerUserService->register($validatedData);
        return response()->json($response->toArray(), 201);
    }

    /**
     * logs a user and return a JWT by credentials through Basic auth.
     * 取得したのユーザーのcredentialsをデータベースに確認してから合ってるならJWTは作成されてレスポンスで返されます。
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     * @throws Exception|Throwable
     */
    public function login(Request $request): JsonResponse
    {
        $dto = new LoginRequestDTO(
            email: $request->getUser(),
            password: $request->getPassword(),
        );
        $response = $this->loginUserService->login(loginRequestDTO: $dto);
        return response()->json($response->toArray(), 201);
    }


    /**
     * この関数によっては取得したのJWTにユーザーID部でログインされているのみのユーザーの個人データをデーターベースに取得されてレスポンスに返事されます。
     * JWTの有効期限されているならExpiredExceptionで例外のエーラを発生されます。
     *
     * @param Request $request
     * @return UserProfileDTO
     * @throws ExpiredException
     * @throws Throwable
     */
    public function getUserProfile(Request $request): UserProfileDTO
    {
        $token = $request->bearerToken();
        return $this->getUserProfile->getUserProfile($token);
    }


    /**
     * ログインされているのユーザーを削除するの関数です。
     * 削除と言うよりIlluminateのORMライブラリーでユーザーテーブルのdeleted_at欄に現実の日付を追加されて保存されます。
     * そうすれば、データーベースにユーザーのデータがされていたままだがIlluminateのORMでユーザーを検索されたら削除されているユーザーが出力されません。
     *
     * @param Request $request
     * @return Response
     * @throws Throwable
     */
    public function deleteUser(Request $request)
    {
        $token = $request->bearerToken();
        abort_if(!$token, 401);
        $this->authService->deleteUser($token);
        return response()->noContent();
    }
}
