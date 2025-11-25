<?php

namespace App\Services;

use App\Models\User;
use App\Services\Traits\HttpClientTrait;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class AuthService
{
    use HttpClientTrait;

    protected mixed $baseUrl;

    public function __construct(protected JWTService $JWTService)
    {
        $this->baseUrl = config('services.member_card.url');

    }

    /**
     * @throws ValidationException
     */
    public function register(array $validator): array
    {
        $emailValid = $validator["email"];
        $emailCrop = strpos($emailValid, "@");
        $avatarImgUrl = $validator["user_name"] . "+" . (substr($emailValid, 0, $emailCrop));

        User::create([
            'user_name' => $validator["user_name"],
            'email' => $validator["email"],
            'avatar_img_url' => "https://avatar.iran.liara.run/username?username=" . $avatarImgUrl,
            'card_uuid' => Uuid::uuid4()->toString(),
            'password' => Hash::make($validator["password"]),
        ]);

        $user = User::where('email', $validator["email"])->firstOrFail();

        if (!$user || !Hash::check($validator["password"], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);

        } else {

            $payload = [
                'iss' => 'library-app-auth',
                'aud' => 'library-app-borrow',
                'sub' => $user->id,
                'user_memberCardUUID' => $user->card_uuid
            ];

            $token = $this->JWTService->createToken($payload);

            $response = $this->makeSafeApiCall("POST", $this->baseUrl . $user->card_uuid, $token, $user->_id);

            $expiresAt = time() + 3600;

            return ["memberCardUUID" => $response["memberCardUUID"], "jwt" => $token, "expires_in" => 3600, 'expires_at' => date('c', $expiresAt)];
        }
    }

    /**
     * @throws ValidationException
     * @throws Exception
     */
    public function login(string $email, string $password): array
    {
        $user = User::where("email", $email)->firstOrFail();

        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $timeInParis = new DateTime("now", new DateTimeZone("Europe/Paris"));
        date_default_timezone_set('Europe/Paris');
        $timeInUTC = $timeInParis->setTimezone(new DateTimeZone('UTC'));

        $user->last_logged_in_at = $timeInUTC;
        $user->save();

        $payload = [
            'iss' => 'library-app-auth',
            'aud' => 'library-app-borrow',
            'sub' => $user->id,
            'user_memberCardUUID' => $user->card_uuid
        ];

        $token = $this->JWTService->createToken($payload);

        $expiresAt = time() + 3600;

        return ["memberCardUUID" => $user->card_uuid, "jwt" => $token, "expires_in" => 3600, 'expires_at' => date('c', $expiresAt)];

    }

    /**
     */
    public function getUserProfile(string $token): array
    {
        $decoded = $this->JWTService->verifyToken($token);

        $user = User::findOrFail($decoded->sub, ['user_name', 'avatar_img_url', 'email', 'card_uuid', 'last_logged_in_at']);

        $response = $this->makeSafeApiCall("GET", $this->baseUrl . $user->card_uuid . '/history?sort=borrow_start_date&direction=asc', $token, $user->_id, (array)null);

        if ($decoded->sub == $user->id) {
            $response = ["user" => $user, "borrowHistory" => $response];
        }

        return $response;
    }

    /**
     */
    public function deleteUser(string $token): null|int
    {
        $decoded = $this->JWTService->verifyToken($token);

        $user = User::findOrFail($decoded->sub);

        $response = $this->makeSafeApiCall("delete", $this->baseUrl . $user->card_uuid, $token, $user->_id);

        if ($response != 503 && is_null($response)) {
            $user->delete();
            return 200;
        }

        return $response;
    }

}
