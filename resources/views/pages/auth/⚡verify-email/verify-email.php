<?php

namespace App\Livewire\Auth;

use App\Notifications\EmailVerificationCode;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    public string $code = '';
    public bool $resent = false;

    protected array $rules = [
        'code' => 'required|string|size:6|regex:/^[0-9]+$/',
    ];

    protected array $messages = [
        'code.required' => 'Please enter the 6-digit code.',
        'code.size'     => 'The code must be exactly 6 digits.',
        'code.regex'    => 'The code must contain only numbers.',
    ];

    public function mount()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($this->dashboardFor($user->role));
        }

        if (!$user->verification_code) {
            $this->sendCode($user);
        }
    }

    public function verify()
    {
        $this->validate();

        $user = Auth::user();

        if (!$user->isVerificationCodeValid($this->code)) {
            $this->addError('code', '❌ Invalid or expired code. Please try again or request a new one.');
            return;
        }

        $user->markEmailAsVerified();

        session()->flash('success', '✅ Email verified! Welcome aboard.');

        return redirect()->intended($this->postVerificationRoute($user));
    }

    public function resend()
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($this->dashboardFor($user->role));
        }

        $this->sendCode($user);
        $this->reset('code');
        $this->resetErrorBag();
        $this->resent = true;
    }

    private function sendCode($user): void
    {
        $code = $user->generateVerificationCode();
        $user->notify(new EmailVerificationCode($code));
    }

    private function dashboardFor(string $role): string
    {
        return match ($role) {
            'admin'          => route('admin.dashboard'),
            'owner'          => route('owner.dashboard'),
            'branch_manager' => route('branch.dashboard'),
            'cashier'        => route('branch.cashier'),
            'barista'        => route('branch.barista'),
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

        return $this->dashboardFor($user->role);
    }

    public function render()
    {
        return view('pages.auth.⚡verify-email.verify-email');
    }
};
