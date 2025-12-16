<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Http\Traits\ResponseTrait;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\RefreshTokenRepository;
use Carbon\Carbon;
use App\Models\User;
use App\Models\PasswordPolicy;
use Auth;
use Laravel\Passport\Client;
use GuzzleHttp\Client as GuzzleClient;

class LoginController extends Controller
{
    use ResponseTrait, AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username()
    {
        return 'user_id';
    }

    public function login(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'password' => 'required',
            'pat' => 'nullable|boolean'
        ]);

        $user = User::where('user_id', $request->user_id)->first();

        if (!$user) {
            return $this->failure('User not found', 200);
        }

        if (!$user->password) {
            return $this->failure('Please perform First Time Login', 200);
        }

        // Increment login attempt & pre-login checks
        Controller::login_attempt($user->user_id);
        if ($preCheck = Controller::pre_login_check($user->user_id)) {
            return response()->json($preCheck);
        }

        if (!Auth::attempt($request->only('user_id', 'password'))) {
            return $this->handleFailedLogin($user);
        }

        Controller::reset_login_attempt($user->user_id);

        if ($postCheck = Controller::post_login_check($user->user_id)) {
            return response()->json($postCheck);
        }

        if (!$user->is_active) {
            return $this->failure('Your account was deactivated', 200);
        }

        if ($user->is_locked) {
            return $this->failure('Your account was locked', 200);
        }

        if ($request->pat) {
            $tokenData = $user->createToken("User {$user->id} Personal Access Token");
            $tokenObj = [
                'token_type' => 'Bearer',
                'expires_in' => (int)(config('app.passport_personal_access_tokens_expire_in') * 24 * 60 * 60),
                'expires_on' => $tokenData->token->expires_at,
                'access_token' => $tokenData->accessToken,
                'token_id' => $tokenData->token->id,
                'token_name' => $tokenData->token->name
            ];
        } else {
            $tokenObj = $this->issuePasswordGrantToken($request->user_id, $request->password);
        }

        // Log current login
        $user->update([
            'current_signin' => Carbon::now(),
            'last_signin' => $user->current_signin
        ]);

        $user->getAllPermissions();

        return response()->json([
            'status' => true,
            'message' => $user->is_force_change ? 'Your password has expired. Please change your password.' : 'Login successful',
            'token' => $tokenObj,
            'user' => $user
        ]);
    }

    private function handleFailedLogin($user)
    {
        $max_attempt = PasswordPolicy::find(6);
        $grace = PasswordPolicy::find(8);
        $max_attempt_val = ($max_attempt && $max_attempt->status) ? (int)$max_attempt->value : 0;
        $grace_val = ($grace && $grace->status) ? (int)$grace->value : 0;

        return response()->json([
            'status' => false,
            'message' => 'Invalid login credentials',
            'max_attempt' => $max_attempt_val,
            'grace' => $grace_val
        ]);
    }

    private function issuePasswordGrantToken($username, $password)
    {
        $passwordClient = Client::find(config('app.passport_client_id'));

        $http = new GuzzleClient();
        $response = $http->post(config('app.passport_login_endpoint'), [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $passwordClient->id,
                'client_secret' => $passwordClient->secret,
                'username' => $username,
                'password' => $password,
                'scope' => ''
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        return [
            'token_type' => $data['token_type'],
            'expires_in' => $data['expires_in'],
            'expires_on' => Carbon::now()->addSeconds($data['expires_in']),
            'refresh_expires_in' => (int)config('app.passport_refresh_tokens_expire_in') * 60,
            'refresh_expires_on' => Carbon::now()->addMinutes(config('app.passport_refresh_tokens_expire_in')),
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token']
        ];
    }

    public function refreshToken(Request $request)
    {
        $request->validate(['refresh_token' => 'required']);

        $passwordClient = Client::find(config('app.passport_client_id'));

        $http = new GuzzleClient();
        $response = $http->post(config('app.passport_login_endpoint'), [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $request->refresh_token,
                'client_id' => $passwordClient->id,
                'client_secret' => $passwordClient->secret,
                'scope' => ''
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        return response()->json([
            'status' => true,
            'message' => 'Token refreshed successfully',
            'token' => [
                'token_type' => $data['token_type'],
                'expires_in' => $data['expires_in'],
                'expires_on' => Carbon::now()->addSeconds($data['expires_in']),
                'refresh_expires_in' => (int)config('app.passport_refresh_tokens_expire_in') * 60,
                'refresh_expires_on' => Carbon::now()->addMinutes(config('app.passport_refresh_tokens_expire_in')),
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token']
            ]
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $tokenRepository = app(TokenRepository::class);
            $refreshTokenRepository = app(RefreshTokenRepository::class);

            if ($request->bearerToken()) {
                $jwt = explode('.', $request->bearerToken());
                $token_id = json_decode(base64_decode($jwt[1]))->jti;

                $tokenRepository->revokeAccessToken($token_id);
                $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($token_id);
            }

            return $this->success('Logout successful');
        } catch (\Throwable $e) {
            report($e);
            return $this->failed('Token revocation failed');
        }
    }
}
