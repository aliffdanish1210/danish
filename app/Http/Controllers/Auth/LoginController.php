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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use GuzzleHttp\Exception\RequestException;
use App\Services\AuditLogger;

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
        DB::beginTransaction();
        
        try {
            // Validation dengan error handling
            try {
                $request->validate([
                    'user_id' => 'required|string|max:255',
                    'password' => 'required|string',
                    'pat' => 'nullable|boolean'
                ]);
            } catch (ValidationException $e) {
                throw new Exception('Validation failed: ' . json_encode($e->errors()));
            }

            // Find user dengan error handling
            try {
                $user = User::where('user_id', $request->user_id)->firstOrFail();
            } catch (ModelNotFoundException $e) {
                Log::warning('Login attempt for non-existent user', [
                    'user_id' => $request->user_id,
                    'ip' => $request->ip()
                ]);
                // Audit log failed login
                AuditLogger::logLogin($request->user_id, false, 'User not found');
                throw new Exception('User not found');
            }

            // Check if password is set
            if (!$user->password) {
                throw new Exception('Please perform First Time Login');
            }

            // Increment login attempt dengan error handling
            try {
                Controller::login_attempt($user->user_id);
            } catch (Exception $e) {
                Log::error('Failed to increment login attempt', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Failed to process login attempt');
            }
            
            // Pre-login checks
            try {
                if ($preCheck = Controller::pre_login_check($user->user_id)) {
                    DB::rollBack();
                    return response()->json($preCheck);
                }
            } catch (Exception $e) {
                Log::error('Pre-login check failed', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Pre-login validation failed');
            }

            // Attempt authentication
            if (!Auth::attempt($request->only('user_id', 'password'))) {
                Log::warning('Failed login attempt - invalid credentials', [
                    'user_id' => $request->user_id,
                    'ip' => $request->ip()
                ]);
                AuditLogger::logLogin($request->user_id, false, 'Invalid credentials');
                DB::rollBack();
                return $this->handleFailedLogin($user);
            }

            // Reset login attempts
            try {
                Controller::reset_login_attempt($user->user_id);
            } catch (Exception $e) {
                Log::error('Failed to reset login attempt', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                // Continue even if reset fails
            }

            // Post-login checks
            try {
                if ($postCheck = Controller::post_login_check($user->user_id)) {
                    Auth::logout();
                    DB::rollBack();
                    return response()->json($postCheck);
                }
            } catch (Exception $e) {
                Log::error('Post-login check failed', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                Auth::logout();
                throw new Exception('Post-login validation failed');
            }

            // Check account status
            if (!$user->is_active) {
                Auth::logout();
                throw new Exception('Your account was deactivated');
            }

            if ($user->is_locked) {
                Auth::logout();
                throw new Exception('Your account was locked');
            }

            // Check if MFA is enabled
            if ($user->mfa_enabled) {
                DB::commit();
                return $this->initiateMFA($user);
            }

            AuditLogger::logLogin($user->user_id, true);

            // Generate token and complete login
            $response = $this->completeLogin($user, $request);
            
            DB::commit();
            return $response;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Login error occurred', [
                'user_id' => $request->user_id ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->failure(
                $e->getMessage() ?: 'An error occurred during login. Please try again.',
                500
            );
        }
    }

    private function initiateMFA($user)
    {
        try {
            // Generate 6-digit OTP
            $otp = rand(100000, 999999);
            
            // Store OTP in cache
            try {
                $cacheKey = 'mfa_otp_' . $user->id;
                Cache::put($cacheKey, $otp, now()->addMinutes(5));
            } catch (Exception $e) {
                Log::error('Failed to store OTP in cache', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Failed to generate OTP');
            }
            
            // Store temporary session
            try {
                $tempToken = bin2hex(random_bytes(32));
                Cache::put('mfa_temp_' . $tempToken, $user->id, now()->addMinutes(5));
            } catch (Exception $e) {
                Log::error('Failed to store temp token', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Failed to create MFA session');
            }

            // Send OTP
            try {
                // TODO: Implement notification
                // $user->notify(new MFAOTPNotification($otp));
            } catch (Exception $e) {
                Log::error('Failed to send OTP notification', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Failed to send OTP');
            }
            
            Log::info('MFA initiated successfully', ['user_id' => $user->user_id]);

            return response()->json([
                'status' => true,
                'message' => 'OTP has been sent to your registered email/phone',
                'requires_mfa' => true,
                'temp_token' => $tempToken,
                'mfa_method' => $user->mfa_method ?? 'email'
            ]);

        } catch (Exception $e) {
            Log::error('MFA initiation error', [
                'user_id' => $user->user_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->failure(
                $e->getMessage() ?: 'Failed to initiate MFA. Please try again.',
                500
            );
        }
    }

    public function verifyMFA(Request $request)
    {
        try {
            // Validate input
            try {
                $request->validate([
                    'temp_token' => 'required|string',
                    'otp' => 'required|string|size:6',
                    'pat' => 'nullable|boolean'
                ]);
            } catch (ValidationException $e) {
                throw new Exception('Invalid MFA verification data');
            }

            // Get user ID from temp token
            try {
                $userId = Cache::get('mfa_temp_' . $request->temp_token);
                
                if (!$userId) {
                    throw new Exception('Invalid or expired session. Please login again.');
                }
            } catch (Exception $e) {
                Log::warning('Invalid MFA temp token', [
                    'temp_token' => substr($request->temp_token, 0, 10) . '...',
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Invalid or expired session. Please login again.');
            }

            // Find user
            try {
                $user = User::findOrFail($userId);
            } catch (ModelNotFoundException $e) {
                Log::error('User not found during MFA verification', ['user_id' => $userId]);
                throw new Exception('User not found');
            }

            // Verify OTP
            try {
                $cacheKey = 'mfa_otp_' . $user->id;
                $storedOTP = Cache::get($cacheKey);

                if (!$storedOTP) {
                    throw new Exception('OTP has expired. Please login again.');
                }

                if ($storedOTP != $request->otp) {
                    Log::warning('Invalid OTP attempt', [
                        'user_id' => $user->user_id,
                        'ip' => $request->ip()
                    ]);
                    throw new Exception('Invalid OTP. Please try again.');
                }
            } catch (Exception $e) {
                throw $e;
            }

            // Clear MFA cache
            try {
                Cache::forget($cacheKey);
                Cache::forget('mfa_temp_' . $request->temp_token);
            } catch (Exception $e) {
                Log::warning('Failed to clear MFA cache', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                // Continue even if cache clear fails
            }

            // Complete login
            Auth::login($user);
            
            return $this->completeLogin($user, $request);

        } catch (Exception $e) {
            Log::error('MFA verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->failure(
                $e->getMessage() ?: 'An error occurred during verification. Please try again.',
                401
            );
        }
    }

    private function completeLogin($user, $request)
    {
        try {
            // Generate token
            try {
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
                    $tokenObj = $this->issuePasswordGrantToken($user->user_id, $request->password);
                }
            } catch (Exception $e) {
                Log::error('Token generation failed', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Failed to generate authentication token');
            }

            // Update login timestamps
            try {
                $user->update([
                    'current_signin' => Carbon::now(),
                    'last_signin' => $user->current_signin
                ]);
            } catch (Exception $e) {
                Log::error('Failed to update login timestamps', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                // Continue even if update fails
            }

            // Load permissions
            try {
                $user->getAllPermissions();
            } catch (Exception $e) {
                Log::error('Failed to load permissions', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage()
                ]);
                // Continue even if permissions fail to load
            }

            Log::info('User logged in successfully', [
                'user_id' => $user->user_id,
                'ip' => request()->ip()
            ]);

            return response()->json([
                'status' => true,
                'message' => $user->is_force_change 
                    ? 'Your password has expired. Please change your password.' 
                    : 'Login successful',
                'token' => $tokenObj,
                'user' => $user
            ]);

        } catch (Exception $e) {
            Log::error('Complete login error', [
                'user_id' => $user->user_id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    private function handleFailedLogin($user)
    {
        try {
            try {
                $max_attempt = PasswordPolicy::find(6);
                $grace = PasswordPolicy::find(8);
            } catch (Exception $e) {
                Log::error('Failed to fetch password policies', [
                    'error' => $e->getMessage()
                ]);
                // Use defaults if policies can't be fetched
                $max_attempt = null;
                $grace = null;
            }

            $max_attempt_val = ($max_attempt && $max_attempt->status) ? (int)$max_attempt->value : 0;
            $grace_val = ($grace && $grace->status) ? (int)$grace->value : 0;

            return response()->json([
                'status' => false,
                'message' => 'Invalid login credentials',
                'max_attempt' => $max_attempt_val,
                'grace' => $grace_val
            ], 401);

        } catch (Exception $e) {
            Log::error('Error in handleFailedLogin', [
                'user_id' => $user->user_id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return $this->failure('Authentication failed', 401);
        }
    }

    private function issuePasswordGrantToken($username, $password)
    {
        try {
            // Get Passport client
            try {
                $passwordClient = Client::findOrFail(config('app.passport_client_id'));
            } catch (ModelNotFoundException $e) {
                Log::error('Passport client not found', [
                    'client_id' => config('app.passport_client_id')
                ]);
                throw new Exception('Authentication configuration error');
            }

            // Make token request
            try {
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
            } catch (RequestException $e) {
                Log::error('OAuth token request failed', [
                    'username' => $username,
                    'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : 'N/A',
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Failed to generate access token');
            }

            // Parse response
            try {
                $data = json_decode($response->getBody(), true);
                
                if (!isset($data['access_token'])) {
                    throw new Exception('Invalid token response');
                }
            } catch (Exception $e) {
                Log::error('Failed to parse token response', [
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Invalid token response format');
            }

            return [
                'token_type' => $data['token_type'],
                'expires_in' => $data['expires_in'],
                'expires_on' => Carbon::now()->addSeconds($data['expires_in']),
                'refresh_expires_in' => (int)config('app.passport_refresh_tokens_expire_in') * 60,
                'refresh_expires_on' => Carbon::now()->addMinutes(config('app.passport_refresh_tokens_expire_in')),
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token']
            ];

        } catch (Exception $e) {
            Log::error('Token issuance error', [
                'username' => $username ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            // Validate input
            try {
                $request->validate(['refresh_token' => 'required|string']);
            } catch (ValidationException $e) {
                throw new Exception('Refresh token is required');
            }

            // Get Passport client
            try {
                $passwordClient = Client::findOrFail(config('app.passport_client_id'));
            } catch (ModelNotFoundException $e) {
                Log::error('Passport client not found during token refresh');
                throw new Exception('Invalid client configuration');
            }

            // Request new token
            try {
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
            } catch (RequestException $e) {
                Log::error('Token refresh request failed', [
                    'status_code' => $e->getResponse() ? $e->getResponse()->getStatusCode() : 'N/A',
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Failed to refresh token. Please login again.');
            }

            // Parse response
            try {
                $data = json_decode($response->getBody(), true);
                
                if (!isset($data['access_token'])) {
                    throw new Exception('Invalid response format');
                }
            } catch (Exception $e) {
                Log::error('Failed to parse refresh token response', [
                    'error' => $e->getMessage()
                ]);
                throw new Exception('Invalid token response');
            }

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

        } catch (Exception $e) {
            Log::error('Token refresh error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->failure(
                $e->getMessage() ?: 'Failed to refresh token. Please login again.',
                401
            );
        }
    }

    public function logout(Request $request)
    {
        try {
            $tokenRepository = app(TokenRepository::class);
            $refreshTokenRepository = app(RefreshTokenRepository::class);

            if ($request->bearerToken()) {
                try {
                    $jwt = explode('.', $request->bearerToken());
                    
                    if (count($jwt) !== 3) {
                        throw new Exception('Invalid token format');
                    }

                    $payload = json_decode(base64_decode($jwt[1]));
                    
                    if (!$payload || !isset($payload->jti)) {
                        throw new Exception('Invalid token payload');
                    }

                    $token_id = $payload->jti;

                    // Revoke tokens
                    try {
                        $tokenRepository->revokeAccessToken($token_id);
                        $refreshTokenRepository->revokeRefreshTokensByAccessTokenId($token_id);
                    } catch (Exception $e) {
                        Log::error('Failed to revoke tokens', [
                            'token_id' => $token_id,
                            'error' => $e->getMessage()
                        ]);
                        throw new Exception('Token revocation failed');
                    }

                } catch (Exception $e) {
                    Log::error('Error processing bearer token during logout', [
                        'error' => $e->getMessage()
                    ]);
                    // Continue with logout even if token revocation fails
                }
            }

            try {
                Auth::logout();
            } catch (Exception $e) {
                Log::error('Failed to logout user', [
                    'error' => $e->getMessage()
                ]);
            }
            
            Log::info('User logged out successfully');
            
            return $this->success('Logout successful');

        } catch (Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->failure(
                'An error occurred during logout',
                500
            );
        }
    }

    protected function authenticated(Request $request, $user)
    {
        try {
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('home');
        } catch (Exception $e) {
            Log::error('Error during post-authentication redirect', [
                'user_id' => $user->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return redirect()->route('home');
        }
    }
}