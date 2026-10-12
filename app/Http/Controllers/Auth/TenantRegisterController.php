<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LoyaltySetting;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class TenantRegisterController extends Controller
{
    private const SESSION_KEYS = [
        'register_data',
        'register_code',
        'register_code_expires_at',
        'code_sent',
    ];

    public function showForm(Request $request)
    {
        $shouldReset = $request->boolean('fresh')
                    || !$request->headers->has('referer');

        if ($shouldReset) {
            session()->forget(self::SESSION_KEYS);
        }

        return view('pages.auth.⚡tenant-register.tenant-register');
    }

    public function startOver()
    {
        session()->forget(self::SESSION_KEYS);

        return redirect()->route('register.owner.form');
    }

    public function sendCode(Request $request)
    {
        // ✅ RESEND
        if ($request->boolean('resend')) {
            $data = session('register_data');

            if (!$data) {
                return redirect()->route('register.owner.form')
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
            'first_name'         => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s\.\-\']+$/',
            'middle_name'        => 'nullable|string|max:50|regex:/^[a-zA-Z\s\.\-\']+$/',
            'last_name'          => 'required|string|min:2|max:50|regex:/^[a-zA-Z\s\.\-\']+$/',
            'email'              => 'required|email:rfc,dns|max:255',
            'password'           => 'required|confirmed|min:8|max:64',
            'shop_name'          => 'required|string|min:2|max:100',
            'business_type'      => 'required|in:sole_proprietor,partnership,corporation,individual',
            'owner_phone'        => ['required', 'string', 'regex:/^(09|\+639)\d{9}$/'],
            'business_barangay'  => 'required|string|max:150',
            'business_street'    => 'nullable|string|max:255',
            'owner_id'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'email.email'                => 'Please enter a valid email address.',
            'first_name.regex'           => 'First name can only contain letters, spaces, dots, hyphens, and apostrophes.',
            'first_name.min'             => 'First name must be at least 2 characters.',
            'first_name.max'             => 'First name cannot exceed 50 characters.',
            'middle_name.regex'          => 'Middle name can only contain letters, spaces, dots, hyphens, and apostrophes.',
            'middle_name.max'            => 'Middle name cannot exceed 50 characters.',
            'last_name.regex'            => 'Last name can only contain letters, spaces, dots, hyphens, and apostrophes.',
            'last_name.min'              => 'Last name must be at least 2 characters.',
            'last_name.max'              => 'Last name cannot exceed 50 characters.',
            'shop_name.min'              => 'Shop name must be at least 2 characters.',
            'shop_name.max'              => 'Shop name cannot exceed 100 characters.',
            'password.min'               => 'Password must be at least 8 characters.',
            'password.max'               => 'Password cannot exceed 64 characters.',
            'owner_phone.regex'          => 'Phone must be a valid PH number (e.g. 09123456789 or +639123456789).',
            'business_street.max'        => 'Street address cannot exceed 255 characters.',
            'business_barangay.required' => 'Please select a barangay.',
        ]);

        $email = strtolower(trim($request->email));

        // ✅ Duplicate check on BOTH tables (includes soft-deleted rows)
        $emailState = $this->emailState($email);

        if ($emailState !== null) {
            return back()
                ->withInput()
                ->withErrors(['email' => $this->emailTakenMessage($emailState)]);
        }

        $key = 'send-code:' . $email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return back()
                ->withInput()
                ->with('error', "Too many requests. Please wait {$seconds} seconds.");
        }
        RateLimiter::hit($key, 600);

        $ownerIdPath = $request->file('owner_id')->store('tenants/owner_ids', 'public');

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $firstName  = $this->titleCase(trim(strip_tags($request->first_name)));
        $middleName = $request->filled('middle_name') ? $this->titleCase(trim(strip_tags($request->middle_name))) : null;
        $lastName   = $this->titleCase(trim(strip_tags($request->last_name)));

        $fullName = collect([$firstName, $middleName, $lastName])
            ->filter()
            ->implode(' ');

        $barangay = trim(strip_tags($request->business_barangay));
        $street   = $request->filled('business_street') ? trim(strip_tags($request->business_street)) : null;

        session([
            'register_data' => [
                'first_name'         => $firstName,
                'middle_name'        => $middleName,
                'last_name'          => $lastName,
                'name'               => $fullName,
                'email'              => $email,
                'password'           => Hash::make($request->password),
                'shop_name'          => trim(strip_tags($request->shop_name)),
                'business_type'      => $request->business_type,
                'owner_phone'        => trim(strip_tags($request->owner_phone)),
                'business_barangay'  => $barangay,
                'business_street'    => $street,
                'business_address'   => collect([
                                            $street,
                                            $barangay,
                                            'Victorias City',
                                            'Negros Occidental',
                                        ])->filter()->implode(', '),
                'owner_id_path'      => $ownerIdPath,
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

    private function emailState(string $email): ?string
    {
        $state = null;

        foreach ([User::class, Tenant::class] as $model) {
            $query = $model::query();

            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);
            if ($usesSoftDeletes) {
                $query->withTrashed();
            }

            $row = $query->whereRaw('LOWER(email) = ?', [$email])->first();

            if (!$row) {
                continue;
            }

            if ($usesSoftDeletes && $row->trashed()) {
                $state = $state ?? 'trashed';
            } else {
                return 'active';
            }
        }

        return $state;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'shop';
        $slug = $base;
        $i    = 2;

        while (DB::table('tenants')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function emailTakenMessage(string $state): string
    {
        return $state === 'trashed'
            ? 'This email belongs to a previously deleted account. Please use a different email or contact support.'
            : 'This email has already been registered. Please log in instead.';
    }

    private function duplicateKeyMessage(QueryException $e): array
    {
        $raw = $e->getMessage();

        preg_match("/for key '([^']+)'/i", $raw, $m);
        $key = strtolower($m[1] ?? $raw);

        if (str_contains($key, 'email')) {
            return ['code', 'This email has already been registered. Please log in instead.'];
        }
        if (str_contains($key, 'phone')) {
            return ['code', 'This phone number is already used by another account.'];
        }
        if (str_contains($key, 'name')) {
            return ['code', 'A shop with this name already exists. Please start over and choose a different shop name.'];
        }

        $hint = app()->hasDebugModeEnabled() ? " (duplicate key: {$key})" : '';

        return ['code', 'Some of your details conflict with an existing record. Please start over or contact support.' . $hint];
    }

    public function registerOwner(Request $request)
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
            session()->forget(self::SESSION_KEYS);
            return back()->with('error', 'The code has expired. Please request a new one.');
        }

        $verifyKey = 'verify-code:' . md5($data['email']);
        if (RateLimiter::tooManyAttempts($verifyKey, 5)) {
            $seconds = RateLimiter::availableIn($verifyKey);
            return back()->with('error', "Too many wrong attempts. Please wait {$seconds} seconds.");
        }

        if (!hash_equals((string) $code, $request->code)) {
            RateLimiter::hit($verifyKey, 900);
            session(['code_sent' => true]);
            return back()->withErrors(['code' => 'Invalid code. Please try again.']);
        }

        RateLimiter::clear($verifyKey);

        $email = strtolower(trim($data['email']));

        // ✅ PRE-CHECK before the transaction
        $emailState = $this->emailState($email);

        if ($emailState !== null) {
            session()->forget(self::SESSION_KEYS);
            return back()->withErrors(['code' => $this->emailTakenMessage($emailState)]);
        }

        try {
            DB::beginTransaction();

            // ✅ Create USER first
            $user = User::create([
                'name'              => $data['name'],
                'email'             => $email,
                'password'          => $data['password'],
                'tenant_id'         => null,
                'role'              => 'owner',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]);

            // ✅ Create TENANT — the tenant IS the own store
            $tenant = new Tenant([
                'name'             => $data['shop_name'],
                'code'             => (string) Str::uuid(),
                'status'           => 'pending',
                'email'            => $email,
                'business_type'    => $data['business_type'],
                'business_address' => $data['business_address'],
                'owner_name'       => $data['name'],
                'owner_phone'      => $data['owner_phone'],
                'owner_id_path'    => $data['owner_id_path'],
            ]);
            $tenant->slug = $this->uniqueSlug($data['shop_name']);
            $tenant->save();

            // ✅ Link the user to the tenant. Owner's branch_id stays null —
            //    the tenant itself is the shop, no main branch row needed.
            $user->update(['tenant_id' => $tenant->id]);

            // ✅ Loyalty settings
            LoyaltySetting::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'is_enabled'           => false,
                    'points_per_peso'      => 1,
                    'points_per_order'     => 10,
                    'points_expiry_days'   => 365,
                    'min_points_to_redeem' => 100,
                    'points_value_in_peso' => 1,
                ]
            );

            // ✅ Default categories
            foreach (['Coffee', 'Pastries', 'Desserts', 'Beverages'] as $catName) {
                Category::firstOrCreate([
                    'tenant_id' => $tenant->id,
                    'cat_name'  => $catName,
                    'type'      => 'finished',
                ]);
            }

            DB::commit();

        } catch (QueryException $e) {
            DB::rollBack();

            Log::error('Tenant registration DB error: ' . $e->getMessage());

            $isDuplicate = ($e->errorInfo[1] ?? null) === 1062
                        || ($e->errorInfo[0] ?? $e->getCode()) === '23000'
                        || ($e->errorInfo[0] ?? null) === '23505';

            if ($isDuplicate) {
                [$field, $message] = $this->duplicateKeyMessage($e);
                session()->forget(self::SESSION_KEYS);
                return back()->withErrors([$field => $message]);
            }

            return back()->withInput()->with('error', 'Registration failed. Please try again.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Tenant registration failed: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Registration failed. Please try again.');
        }

        session()->forget(self::SESSION_KEYS);

        Auth::login($user);
        session(['tenant_id' => $tenant->id]);

        return redirect()->route('approval-pending', ['type' => 'tenant'])
            ->with('info', 'Account created! Please wait for admin approval.');
    }
}