<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\EmailVerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class EmailVerificationController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($this->dashboardFor($user));
        }

        if (!$user->verification_code) {
            $user->notify(new EmailVerificationCode($user->generateVerificationCode()));
        }

        return view('pages.auth.⚡verify-email.verify-email');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6|regex:/^[0-9]+$/',
        ], [
            'code.required' => 'Please enter the 6-digit code.',
            'code.size'     => 'The code must be exactly 6 digits.',
            'code.regex'    => 'The code must contain only numbers.',
        ]);

        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // ✅ Brute-force protection — 5 wrong attempts per user per 15 min
        $verifyKey = 'verify-email-code:' . $user->id;
        if (RateLimiter::tooManyAttempts($verifyKey, 5)) {
            $user->update([
                'verification_code'            => null,
                'verification_code_expires_at' => null,
            ]);
            RateLimiter::clear($verifyKey);
            return back()->withErrors([
                'code' => '❌ Too many wrong attempts. Please request a new code.',
            ]);
        }

        if (!$user->isVerificationCodeValid($request->code)) {
            RateLimiter::hit($verifyKey, 900); // 15 min
            return back()->withErrors([
                'code' => '❌ Invalid or expired code. Please try again.',
            ]);
        }

        RateLimiter::clear($verifyKey);

        $user->markEmailAsVerified();

        return redirect()
            ->intended($this->postVerificationRoute($user))
            ->with('success', '✅ Email verified! Welcome aboard.');
    }

    public function resend()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($this->dashboardFor($user));
        }

        // ✅ Rate limit — 3 resends per user per 10 min
        $key = 'resend-code:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->with('error', "Too many requests. Please wait {$seconds} seconds.");
        }
        RateLimiter::hit($key, 600);

        $user->notify(new EmailVerificationCode($user->generateVerificationCode()));

        return back()->with('resent', true);
    }

    private function dashboardFor($user): string
    {
        return match ($user->role) {
            'admin'          => route('admin.dashboard'),
            'owner'          => route('owner.dashboard'),
            'branch_manager' => $user->branch_id ? route('branch.dashboard') : route('owner.dashboard'),
            'cashier'        => $user->branch_id ? route('branch.cashier') : route('cashier.dashboard'),
            'barista'        => $user->branch_id ? route('branch.barista') : route('tenant.barista'),
            'customer'       => route('customer.dashboard'),
            default          => route('public.landing'),
        };
    }

    private function postVerificationRoute($user): string
    {
        if ($user->role === 'owner' && $user->tenant_id) {
            $hasApprovedBranch = \App\Models\Branch::where('tenant_id', $user->tenant_id)
                ->where('approval_status', 'approved')
                ->exists();

            if (!$hasApprovedBranch) {
                return route('approval-pending', ['type' => 'tenant']);
            }
        }

        if ($user->isBranchStaff() && $user->branch_id) {
            $branch = \App\Models\Branch::find($user->branch_id);

            if ($branch && $branch->approval_status === 'pending') {
                return route('approval-pending', ['type' => 'branch']);
            }
        }

        return $this->dashboardFor($user);
    }
}
