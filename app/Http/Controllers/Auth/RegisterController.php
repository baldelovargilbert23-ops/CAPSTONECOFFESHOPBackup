<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class RegisterController extends Controller
{
    public function showForm(Request $request)
    {
        $shouldReset = $request->boolean('fresh')
                    || !$request->headers->has('referer');

        if ($shouldReset) {
            session()->forget([
                'register_data',
                'register_code',
                'register_code_expires_at',
                'code_sent',
            ]);
        }

        return view('pages.auth.⚡register.register');
    }

    public function startOver()
    {
        session()->forget([
            'register_data',
            'register_code',
            'register_code_expires_at',
            'code_sent',
        ]);

        return redirect()->route('register.customer.form');
    }

    public function sendCode(Request $request)
    {
        // ✅ RESEND
        if ($request->boolean('resend')) {
            $data = session('register_data');

            if (!$data) {
                return redirect()->route('register.customer.form')
                    ->with('error', 'Your session expired. Please fill the form again.');
            }

            $key = 'send-code:' . strtolower($data['email']);
            if (RateLimiter::tooManyAttempts($key, 3)) {
                $seconds = RateLimiter::availableIn($key);
                return back()->with('error', "Too many requests. Please wait {$seconds} seconds.");
            }
            RateLimiter::hit($key, 600);

            $newCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            session([
                'register_code'            => $newCode,
                'register_code_expires_at' => now()->addMinutes(15)->timestamp,
            ]);

            $tempUser = new User([
                'name'  => $data['name'],
                'email' => $data['email'],
            ]);
            $tempUser->notify(new EmailVerificationCode($newCode));

            session(['code_sent' => true]);

            return back()->with('info', 'We sent a new 6-digit code to ' . $data['email']);
        }

        // ✅ INITIAL SEND
        $request->validate([
            'first_name'  => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s\.\-\']+$/',
            'middle_name' => 'nullable|string|max:50|regex:/^[a-zA-Z\s\.\-\']+$/',
            'last_name'   => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s\.\-\']+$/',
            'email'       => 'required|email:rfc,dns|max:255',
            'password'    => 'required|confirmed|min:8|max:64',
            'terms'       => 'required|accepted',
        ], [
            'terms.required'      => 'You must agree to the Terms and Privacy Policy to continue.',
            'terms.accepted'      => 'You must agree to the Terms and Privacy Policy to continue.',
            'email.email'         => 'Please enter a valid email address.',
            'first_name.regex'    => 'First name can only contain letters, spaces, dots, hyphens, and apostrophes.',
            'first_name.min'      => 'First name must be at least 2 characters.',
            'first_name.max'      => 'First name cannot exceed 50 characters.',
            'middle_name.regex'   => 'Middle name can only contain letters, spaces, dots, hyphens, and apostrophes.',
            'middle_name.max'     => 'Middle name cannot exceed 50 characters.',
            'last_name.regex'     => 'Last name can only contain letters, spaces, dots, hyphens, and apostrophes.',
            'last_name.min'       => 'Last name must be at least 2 characters.',
            'last_name.max'       => 'Last name cannot exceed 50 characters.',
            'password.min'        => 'Password must be at least 8 characters.',
            'password.max'        => 'Password cannot exceed 64 characters.',
        ]);

        $email = strtolower(trim($request->email));

        $userExists = User::whereRaw('LOWER(email) = ?', [$email])->exists();

        if ($userExists) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'This email is already registered. Please log in instead.']);
        }

        $key = 'send-code:' . $email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()
                ->withInput()
                ->with('error', "Too many requests. Please wait {$seconds} seconds before requesting a new code.");
        }
        RateLimiter::hit($key, 600);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $firstName  = $this->titleCase(trim(strip_tags($request->first_name)));
        $middleName = $request->filled('middle_name') ? $this->titleCase(trim(strip_tags($request->middle_name))) : null;
        $lastName   = $this->titleCase(trim(strip_tags($request->last_name)));

        $fullName = collect([$firstName, $middleName, $lastName])
            ->filter()
            ->implode(' ');

        session([
            'register_data' => [
                'first_name'  => $firstName,
                'middle_name' => $middleName,
                'last_name'   => $lastName,
                'name'        => $fullName,
                'email'       => $email,
                'password'    => Hash::make($request->password),
                'role'        => 'customer',
            ],
            'register_code'            => $code,
            'register_code_expires_at' => now()->addMinutes(15)->timestamp,
        ]);

        $tempUser = new User([
            'name'  => $fullName,
            'email' => $email,
        ]);
        $tempUser->notify(new EmailVerificationCode($code));

        session(['code_sent' => true]);

        return back()->with('info', 'We sent a 6-digit code to ' . $email);
    }

    private function titleCase(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value));
        return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    public function registerCustomer(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6|regex:/^[0-9]+$/',
        ], [
            'code.required' => 'Please enter the 6-digit code.',
            'code.size'     => 'The code must be exactly 6 digits.',
            'code.regex'    => 'The code must contain only numbers.',
        ]);

        $data      = session('register_data');
        $code      = session('register_code');
        $expiresAt = session('register_code_expires_at');

        if (!$data || !$code || !$expiresAt) {
            return back()->with('error', 'Your session expired. Please fill the form again.');
        }

        if (now()->timestamp > $expiresAt) {
            session()->forget(['register_data', 'register_code', 'register_code_expires_at', 'code_sent']);
            return back()->with('error', 'The code has expired. Please request a new one.');
        }

        $verifyKey = 'verify-code:' . md5($data['email']);
        if (RateLimiter::tooManyAttempts($verifyKey, 5)) {
            $seconds = RateLimiter::availableIn($verifyKey);
            return back()->with('error', "Too many wrong attempts. Please wait {$seconds} seconds and request a new code.");
        }

        if (!hash_equals((string) $code, $request->code)) {
            RateLimiter::hit($verifyKey, 900);
            session(['code_sent' => true]);
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        RateLimiter::clear($verifyKey);

        $email = strtolower(trim($data['email']));

        try {
            DB::beginTransaction();

            $exists = User::whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                DB::rollBack();
                session()->forget(['register_data', 'register_code', 'register_code_expires_at', 'code_sent']);

                // ✅ Show error on the register page — NOT a redirect to login
                return back()->withErrors([
                    'code' => 'This email has already been registered. Please log in instead.',
                ]);
            }

            $user = User::create([
                'name'              => $data['name'],
                'email'             => $email,
                'password'          => $data['password'],
                'role'              => 'customer',
                'is_active'         => true,
                'tenant_id'         => null,
                'branch_id'         => null,
                'email_verified_at' => now(),
            ]);

            DB::commit();

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            if ($e->errorInfo[1] === 1062) {
                session()->forget(['register_data', 'register_code', 'register_code_expires_at', 'code_sent']);

                // ✅ Show error on the register page — NOT a redirect to login
                return back()->withErrors([
                    'code' => 'This email has already been registered. Please log in instead.',
                ]);
            }

            throw $e;
        }

        session()->forget(['register_data', 'register_code', 'register_code_expires_at', 'code_sent']);

        Auth::login($user);

        return redirect()->route('customer.dashboard')
            ->with('success', 'Account created and verified! Welcome aboard.');
    }
}