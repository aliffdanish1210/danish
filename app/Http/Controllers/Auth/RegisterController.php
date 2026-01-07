<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'     => ['required', 'string', 'max:255'],
            'user_id'  => ['required', 'string', 'max:50', 'unique:users,user_id'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'], 
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name'       => $data['name'],
            'user_id'    => $data['user_id'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'is_active'  => 1,
            'is_locked'  => 0,
            'mfa_enabled' => true,
            'mfa_method' => 'email',
        ]);

        // Assign default role
        $user->assignRole('user');

        return $user;
    }

    /**
     * Handle a registration request for the application.
     * Overriding to send verification email.
     */
    public function register(\Illuminate\Http\Request $request)
    {
        $this->validator($request->all())->validate();

        // Create the user
        $user = $this->create($request->all());

        // Fire Registered event to send verification email
        event(new Registered($user));

        // Optional: log the user in
        $this->guard()->login($user);

        // Redirect to a page telling user to verify email
        return redirect($this->redirectPath())
            ->with('success', 'Registration successful! Please check your email to verify your account.');
    }
}
