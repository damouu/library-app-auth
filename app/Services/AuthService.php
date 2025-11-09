<?php

namespace App\Services;

use App\Models\User;
use DateTime;
use DateTimeZone;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

class AuthService
{
    protected mixed $baseUrl;

    public function __construct(protected JWTService $JWTService)
    {
        $this->baseUrl = config('services.member_card.url');

    }

    /**
     * @throws ValidationException
     */
    public function register(array $validator): string
    {
        $emailValid = $validator["email"];
        $emailCrop = strpos($emailValid, "@");
        $avatarImgUrl = $validator["userName"] . "+" . (substr($emailValid, 0, $emailCrop));

        $uuid = Uuid::uuid4()->toString();
        (new User)->create([
            'userName' => $validator["userName"],
            'email' => $validator["email"],
            'avatar_img_URL' => "https://avatar.iran.liara.run/username?username=" . $avatarImgUrl,
            'memberCardUUID' => $uuid,
            'last_loggedIn_at' => null,
            'deleted_at' => null,
            'updated_at' => null,
            'password' => Hash::make($validator["password"]),
        ]);

        $user = (new User)->where('email', $validator["email"])->firstOrFail();

        if (!$user || !Hash::check($validator["password"], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);

        } else {

            try {
                Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post("{$this->baseUrl}" . $uuid);
            } catch (ClientException $e) {
                var_dump($e->getResponse());
            } catch (ConnectionException $e) {
                echo $e->getMessage();
            }
            $payload = [
                'iss' => 'http://example.org',
                'aud' => 'http://example.com',
                'user_id' => $user->id,
                'user_memberCardUUID' => $user->memberCardUUID
            ];

            return $this->JWTService->createToken($payload);
        }
    }

    /**
     * @throws ValidationException
     * @throws Exception
     */
    public function login(string $email, string $password): string
    {
        $user = (new User)->where("email", $email)->firstOrFail();

        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $time = new DateTime("now", new DateTimeZone("Europe/Paris"));
        date_default_timezone_set('Europe/Paris');
        $user->last_loggedIn_at = $time;
        $user->save();

        $payload = [
            'iss' => 'http://example.org',
            'aud' => 'http://example.com',
            'user_id' => $user->id,
            'user_memberCardUUID' => $user->memberCardUUID
        ];

        return $this->JWTService->createToken($payload);

    }

    public function getUserProfile(string $token): array
    {
        $decoded = $this->JWTService->verifyToken($token);

        $user = (new User)->findOrFail($decoded->user_id);

        try {
            $borrowHistory = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}" . $user->getAttribute('memberCardUUID') . '/history');
        } catch (ClientException $e) {
            var_dump($e->getResponse());
        } catch (ConnectionException $e) {
            echo $e->getMessage();
        }


        if (!empty($borrowHistory)) {
            $lele = json_decode($borrowHistory->getBody()->getContents(), true);
        }

        $response = array();
        if ($decoded->user_id == $user->id) {
            $userData = array(
                "email" => $user->getAttribute('email'),
                "userName" => $user->getAttribute('userName'),
                "memberCardUUID" => $user->getAttribute('memberCardUUID'),
                "last_loggedIn_at" => $user->getAttribute('last_loggedIn_at'),
                "avatar_img_URL" => $user->getAttribute('avatar_img_URL'),
                "created_at" => $user->getAttribute('created_at')->format('Y-m-d H:i:s'),
                "borrows_history" => $lele['borrows_UUID']);
            $response = ['message' => true, "data" => $userData];
        }

        return $response;
    }

    public function deleteUser(string $token): array
    {
        $decoded = $this->JWTService->verifyToken($token);

        $user = (new User)->findOrFail($decoded->user_id);

        if ($decoded->user_id == $user->id) {
            $user->delete();
            try {
                Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->delete("{$this->baseUrl}" . $user->memberCardUUID);
            } catch (ClientException $e) {
                var_dump($e->getResponse());
            } catch (ConnectionException $e) {
                echo $e->getMessage();
            }
            $response = ['status' => 201, "message" => "user deleted successfully"];
        } else {
            $response = ['status' => 404, "message" => "user not found"];
        }
        return $response;
    }

}
