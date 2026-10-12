<?php

namespace App\Http\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Branch;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected array $rules = [
        'email' => 'required|email',
        'password' => 'required|string|min:6',
    ];

    public function login()
    {
        $this->validate();

        // Check if user exists first
        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('email', '❌ No account found with this email address. Please check your email or register.');
            return;
        }

        // Check if user is active
        if (!$user->is_active) {
            $this->addError('email', '❌ Your account has been deactivated. Please contact support for assistance.');
            return;
        }

        // Attempt login
        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            $user = Auth::user();

            // ============================================================
            // CHECK BRANCH STATUS FOR DIFFERENT ROLES
            // ============================================================
            
            // ✅ Admin - no branch check
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            // ✅ Customer - no branch check
            if ($user->role === 'customer') {
                return redirect()->route('customer.dashboard');
            }

            // ✅ Owner - check if they have approved branches
            if ($user->role === 'owner') {
                // Check if tenant exists
                if (!$user->tenant_id) {
                    Auth::logout();
                    session()->invalidate();
                    $this->addError('email', '❌ No tenant assigned to your account. Please contact the administrator.');
                    return;
                }

                $hasApprovedBranch = Branch::where('tenant_id', $user->tenant_id)
                    ->where('approval_status', 'approved')
                    ->exists();

                if (!$hasApprovedBranch) {
                    $hasPendingBranch = Branch::where('tenant_id', $user->tenant_id)
                        ->where('approval_status', 'pending')
                        ->exists();

                    if ($hasPendingBranch) {
                        // The pending page requires an authenticated user.
                        return redirect()->route('approval-pending', ['type' => 'branch']);
                    }

                    // No branch exists - redirect to create
                    return redirect()->route('owner.branches')
                        ->with('info', 'Please create your first branch to get started.');
                }

                return redirect()->route('owner.dashboard');
            }

            // ✅ Branch staff (branch_manager, cashier, barista)
            if (in_array($user->role, ['branch_manager', 'cashier', 'barista'])) {
                // Check if tenant exists
                if (!$user->tenant_id) {
                    Auth::logout();
                    session()->invalidate();
                    $this->addError('email', '❌ No tenant assigned to your account. Please contact the administrator.');
                    return;
                }

                // Check if branch is assigned
                if (!$user->branch_id) {
                    Auth::logout();
                    session()->invalidate();
                    $this->addError('email', '❌ No branch assigned to your account. Please contact the administrator.');
                    return;
                }

                // Check if branch exists and is approved
                $branch = Branch::find($user->branch_id);
                
                if (!$branch) {
                    Auth::logout();
                    session()->invalidate();
                    $this->addError('email', '❌ Your assigned branch no longer exists. Please contact the administrator.');
                    return;
                }

                if ($branch->approval_status === 'pending') {
                    return redirect()->route('approval-pending', ['type' => 'branch']);
                }

                if ($branch->approval_status === 'rejected') {
                    Auth::logout();
                    session()->invalidate();
                    $this->addError('email', '❌ Your assigned branch has been rejected. Please contact support.');
                    return;
                }

                if (!$branch->is_active) {
                    Auth::logout();
                    session()->invalidate();
                    $this->addError('email', '❌ Your assigned branch is inactive. Please contact the administrator.');
                    return;
                }

                // ✅ All checks passed - set session and redirect
                session(['branch_id' => $user->branch_id]);
                session(['tenant_id' => $user->tenant_id]);

                // Redirect based on role
                if ($user->role === 'branch_manager') {
                    return redirect()->route('branch.dashboard');
                }
                if ($user->role === 'cashier') {
                    return redirect()->route('branch.cashier');
                }
                if ($user->role === 'barista') {
                    return redirect()->route('branch.barista');
                }
            }

            // The old generic employee role has no dashboard in the current
            // branch-based staff system. Ask an administrator to assign a
            // supported role instead of redirecting to a missing route.
            if ($user->role === 'employee') {
                Auth::logout();
                session()->invalidate();
                $this->addError('email', 'This account needs a current staff role. Please contact your administrator.');
                return;
            }

            // Default fallback
            return redirect()->route('public.landing');
        }

        // ✅ Password incorrect
        $this->addError('password', '❌ The password you entered is incorrect. Please try again.');
    }

    public function render()
    {
        return view('pages.auth.⚡login.login');
    }
};
