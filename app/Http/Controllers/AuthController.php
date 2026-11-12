<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:individual,small_business'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'business_name' => $validated['business_name'] ?? null,
            'currency' => strtoupper($validated['currency']),
        ]);

        // Auto-create a default checking account
        Account::create([
            'user_id' => $user->id,
            'name' => $user->role === 'small_business' ? 'Business Operating Account' : 'Primary Checking',
            'type' => $user->role === 'small_business' ? 'business_checking' : 'checking',
            'balance' => 0.00,
            'initial_balance' => 0.00,
            'currency' => $user->currency,
            'color' => '#2563eb',
            'description' => 'Default primary account',
        ]);

        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('success', 'Your account has been created successfully! Welcome to FinTrack AI.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out.');
    }

    public function showProfile()
    {
        $user = Auth::user();
        return view('profile.show', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'business_name' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'phone' => ['nullable', 'string', 'max:50'],
            'ai_provider' => ['required', 'in:openai,custom'],
            'ai_api_key' => ['nullable', 'string'],
            'ai_model' => ['nullable', 'string', 'max:100'],
            'ai_base_url' => ['nullable', 'url', 'max:255'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->business_name = $validated['business_name'];
        $user->currency = strtoupper($validated['currency']);
        $user->phone = $validated['phone'];
        $user->ai_provider = $validated['ai_provider'];
        $user->ai_model = $validated['ai_model'] ?: 'gpt-4o-mini';
        $user->ai_base_url = $validated['ai_base_url'];

        if (!empty($validated['ai_api_key'])) {
            $user->ai_api_key = $validated['ai_api_key'];
        }

        $user->save();

        return back()->with('success', 'Profile and settings updated successfully.');
    }
}
