<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('pages.auth.⚡forgot-password.forgot-password');
    }

    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email:rfc,dns|max:255',
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email'    => 'Please enter a valid email address.',
        ]);

        $key = 'send-reset-code:' . strtolower($request->email);
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()
                ->withInput()
                ->with('error', "Too many requests. Please wait {$seconds} seconds before requesting a new code.");
        }
        RateLimiter::hit($key, 600);

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'We couldn\'t find an account with that email address.',
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            return back()->withErrors([
                'email' => 'This email hasn\'t been verified yet. Please complete your registration first.',
            ]);
        }

        $code = $user->generateResetCode();
        $user->notify(new PasswordResetCode($code));

        session(['reset_email' => $user->email]);

        return back()->with([
            'code_sent' => true,
            'info'      => 'We sent a 6-digit reset code to ' . $user->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'code'                  => 'required|string|size:6|regex:/^[0-9]+$/',
            'password'              => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string',
        ], [
            'code.required'         => 'Please enter the 6-digit code.',
            'code.size'             => 'The code must be exactly 6 digits.',
            'code.regex'            => 'The code must contain only numbers.',
            'password.required'     => 'Please enter a new password.',
            'password.min'          => 'Password must be at least 8 characters.',
            'password.confirmed'    => 'Passwords do not match.',
        ]);

        $email = session('reset_email');

        if (!$email) {
            return back()->with('error', 'Your session expired. Please enter your email again.');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            session()->forget('reset_email');
            return back()->with('error', 'Account not found. Please try again.');
        }

        $verifyKey = 'verify-reset-code:' . $user->id;
        if (RateLimiter::tooManyAttempts($verifyKey, 5)) {
            $user->clearResetCode();
            RateLimiter::clear($verifyKey);
            session()->forget('reset_email');
            return back()->with('error', 'Too many wrong attempts. Please start over.');
        }

        if (!$user->isResetCodeValid($request->code)) {
            RateLimiter::hit($verifyKey, 900);
            return back()->withErrors([
                'code' => '❌ Invalid or expired code. Please try again.',
            ]);
        }

        RateLimiter::clear($verifyKey);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $user->clearResetCode();
        session()->forget('reset_email');

        return redirect()->route('login')
            ->with('success', '✅ Password reset successfully! Please log in with your new password.');
    }
}