<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class MobileAuthController extends Controller
{
    public function store(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'code' => ['nullable', 'string', 'size:6'],
        ]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }
        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            if (empty($data['code']) || ! $provider->verify(decrypt($user->two_factor_secret), $data['code'])) {
                throw ValidationException::withMessages(['code' => ['Enter a valid code from your authenticator app.']]);
            }
        }

        return $this->session($user);
    }

    public function register(Request $request, CreateNewUser $creator): JsonResponse
    {
        $user = $creator->create($request->only(['name', 'email', 'password', 'password_confirmation', 'terms']));
        event(new Registered($user));

        return $this->session($user);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($data);

        return response()->json(['message' => 'If that email has an account, a password reset link has been sent.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function session(User $user): JsonResponse
    {
        $user->refresh();

        return response()->json([
            'token' => $user->createToken('TuklasFood mobile', ['*'], now()->addDays(7))->plainTextToken,
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }
}
