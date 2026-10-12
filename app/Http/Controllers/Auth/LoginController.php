<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('pages.auth.⚡login.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            // Check if user is active
            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact support.',
                ])->onlyInput('email');
            }

            $roleName = $user->role;
            $tenantId = $user->tenant_id;
            $branchId = $user->branch_id;

            /* ============================================================
             * ADMIN
             * ============================================================ */
            if ($roleName === 'admin') {
                session(['tenant_id' => $tenantId]);
                return redirect()->route('admin.dashboard');
            }

            /* ============================================================
             * CUSTOMER
             * ============================================================ */
            if ($roleName === 'customer') {
                return redirect()->route('customer.dashboard');
            }

            /* ============================================================
             * OWNER
             * ============================================================ */
            if ($roleName === 'owner') {
                if (! $tenantId) {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => 'No tenant assigned to your account.',
                    ])->onlyInput('email');
                }

                $tenant = Tenant::find($tenantId);

                if (! $tenant) {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => 'Your tenant no longer exists.',
                    ])->onlyInput('email');
                }

                if ($tenant->status === 'pending') {
                    return redirect()->route('approval-pending', ['type' => 'tenant'])
                        ->with('info', '⏳ Your shop is awaiting admin approval.');
                }

                if ($tenant->status === 'rejected') {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => '❌ Your shop has been rejected. Please contact support.',
                    ])->onlyInput('email');
                }

                if ($tenant->status === 'suspended') {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => 'Your shop has been suspended. Please contact support.',
                    ])->onlyInput('email');
                }

                session(['tenant_id' => $tenantId]);

                $hasApprovedBranch = Branch::where('tenant_id', $tenantId)
                    ->where('approval_status', 'approved')
                    ->exists();

                if (! $hasApprovedBranch) {
                    $hasPendingBranch = Branch::where('tenant_id', $tenantId)
                        ->where('approval_status', 'pending')
                        ->exists();

                    if ($hasPendingBranch) {
                        return redirect()->route('approval-pending', ['type' => 'branch'])
                            ->with('info', '⏳ Your branch is awaiting admin approval.');
                    }

                    return redirect()->route('owner.branches')
                        ->with('info', 'Please create your first branch to get started.');
                }

                return redirect()->route('owner.dashboard');
            }

            /* ============================================================
             * BRANCH STAFF — includes MAIN STORE (branch_id = null)
             * ============================================================ */
            if (in_array($roleName, ['branch_manager', 'cashier', 'barista'], true)) {

                // 1) Must have a tenant
                if (! $tenantId) {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => 'No tenant assigned to your account.',
                    ])->onlyInput('email');
                }

                // 2) Tenant must be active
                $tenant = Tenant::find($tenantId);
                if (! $tenant || $tenant->status !== 'active') {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => 'Your shop is not active. Please contact the administrator.',
                    ])->onlyInput('email');
                }

                /* ========================================================
                 * MAIN STORE STAFF (branch_id = null)
                 * ======================================================== */
                if (empty($branchId)) {
                    session(['tenant_id' => $tenantId]);
                    session()->forget('branch_id');

                    return match ($roleName) {
                        'cashier' => redirect()->route('cashier.dashboard')
                            ->with('info', '🏬 You are signed in as Main Store cashier.'),

                        'barista' => redirect()->route('tenant.barista')
                            ->with('info', '🏬 You are signed in as Main Store barista.'),

                        'branch_manager' => redirect()->route('owner.dashboard')
                            ->with('info', '🏬 Main Store manager — manage the shop from the owner dashboard.'),

                        default => redirect()->route('owner.dashboard'),
                    };
                }

                /* ========================================================
                 * SUB-BRANCH STAFF (branch_id = X)
                 * ======================================================== */
                $branch = Branch::find($branchId);

                if (! $branch) {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => 'Your assigned branch no longer exists. Please contact the administrator.',
                    ])->onlyInput('email');
                }

                if ($branch->approval_status === 'pending') {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()
                        ->with('pending_message', '⏳ Your branch is awaiting admin approval. You will be notified once approved.')
                        ->withInput(['email' => $request->email]);
                }

                if ($branch->approval_status === 'rejected') {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => '❌ Your branch has been rejected. Please contact support.',
                    ])->onlyInput('email');
                }

                if (! $branch->is_active) {
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->withErrors([
                        'email' => 'Your branch is currently inactive. Please contact the administrator.',
                    ])->onlyInput('email');
                }

                session(['branch_id' => $branchId]);
                session(['tenant_id' => $tenantId]);

                return match ($roleName) {
                    'branch_manager' => redirect()->route('branch.dashboard'),
                    'cashier'        => redirect()->route('branch.cashier'),
                    'barista'        => redirect()->route('branch.barista'),
                    default          => redirect()->route('public.landing'),
                };
            }

            if ($roleName === 'employee') {
                Auth::logout();
                $request->session()->invalidate();

                return back()->withErrors([
                    'email' => 'This account needs a current staff role. Please contact your administrator.',
                ])->onlyInput('email');
            }

            return redirect()->route('public.landing');
        }

        // Wrong password / no user
        $userExists = \App\Models\User::where('email', $request->email)->first();

        if ($userExists) {
            return back()->withErrors([
                'password' => 'The password you entered is incorrect.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'No account found with this email address.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('public.landing');
    }
}
