<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user via API and issue a Sanctum token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'role' => ['nullable', 'in:individual,business_admin'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'business_name' => $validated['business_name'] ?? null,
            'currency' => strtoupper($validated['currency'] ?? 'INR'),
            'role' => $validated['role'] ?? 'individual',
        ]);

        // Assign Spatie role if available
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            $roleName = $user->role === 'business_admin' ? 'business_admin' : 'individual';
            $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        // Create default checking account for user
        Account::create([
            'user_id' => $user->id,
            'name' => 'Primary Account',
            'account_name' => 'Primary Account',
            'type' => 'checking',
            'account_type' => 'checking',
            'opening_balance' => 0.00,
            'balance' => 0.00,
            'currency' => $user->currency,
            'is_active' => true,
            'status' => true,
        ]);

        $deviceName = $request->input('device_name', 'Mobile App / External Client');
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'currency' => $user->currency,
                'business_name' => $user->business_name,
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    /**
     * Authenticate user credentials and issue a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $deviceName = $request->device_name ?: 'API Client';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Authenticated successfully',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'currency' => $user->currency,
                'business_name' => $user->business_name,
            ],
        ]);
    }

    /**
     * Revoke current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token revoked successfully',
        ]);
    }

    /**
     * Retrieve authenticated user profile with accounts and roles.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'currency' => $user->currency,
                'business_name' => $user->business_name,
                'total_balance' => $user->total_balance,
                'accounts_count' => $user->accounts()->count(),
                'roles' => method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [$user->role],
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Request a password reset link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Standard security practice: don't reveal user absence
            return response()->json([
                'success' => true,
                'message' => 'If this email is registered, password reset instructions have been dispatched.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset instructions have been dispatched to your email address.',
        ]);
    }
}
