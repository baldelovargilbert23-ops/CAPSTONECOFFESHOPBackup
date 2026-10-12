<?php

namespace App\Livewire\Pages\ShopOwner;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.shop_owner')] class extends Component
{
    use WithFileUploads;

    // Profile
    public $name;
    public $email;
    public $phone;

    // Password
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    // Shop
    public $shop_name;
    public $shop_address;
    public $shop_tagline;         // ✅ NEW
    public $shop_introduction;    // ✅ NEW

    // Logo
    public $current_logo;
    public $new_logo;

    // Cover
    public $current_cover;        // ✅ NEW
    public $new_cover;            // ✅ NEW

    // Notifications
    public $notify_orders = true;
    public $notify_low_stock = true;
    public $notify_reviews = true;
    public $notify_staff = true;

    protected function messages()
    {
        return [
            // Profile
            'name.required' => 'Your full name is required.',
            'name.regex'    => 'Name should only contain letters, spaces, and dashes.',
            'email.required'=> 'Email address is required.',
            'email.email'   => 'Please enter a valid email address.',
            'email.unique'  => 'This email is already registered.',
            'phone.regex'   => 'Please enter a valid phone number.',
            'phone.max'     => 'Phone number cannot exceed 20 characters.',

            // Password
            'current_password.required' => 'Current password is required.',
            'new_password.required'     => 'New password is required.',
            'new_password.min'          => 'Password must be at least 8 characters.',
            'new_password.confirmed'    => 'Passwords do not match.',
            'new_password_confirmation.required' => 'Please confirm your new password.',

            // Shop
            'shop_name.required' => 'Shop name is required.',
            'shop_name.regex'    => 'Shop name should only contain letters, numbers, spaces, and dashes.',
            'shop_address.max'   => 'Address cannot exceed 500 characters.',
            'shop_tagline.max'   => 'Tagline cannot exceed 200 characters.',           // ✅ NEW
            'shop_introduction.max' => 'Introduction cannot exceed 2000 characters.',  // ✅ NEW

            // Logo
            'new_logo.image' => 'The logo must be a valid image file.',
            'new_logo.mimes' => 'Logo must be JPG, PNG, WEBP, or SVG.',
            'new_logo.max'   => 'Logo size cannot exceed 2 MB.',

            // Cover
            'new_cover.image' => 'The cover must be a valid image file.',  // ✅ NEW
            'new_cover.mimes' => 'Cover must be JPG, PNG, or WEBP.',       // ✅ NEW
            'new_cover.max'   => 'Cover size cannot exceed 4 MB.',         // ✅ NEW
        ];
    }

    public function mount()
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        $this->name  = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';

        $this->shop_name          = $tenant->name ?? '';
        $this->shop_address       = $tenant->address ?? '';
        $this->shop_tagline       = $tenant->tagline ?? '';        // ✅ NEW
        $this->shop_introduction  = $tenant->introduction ?? '';   // ✅ NEW

        $this->current_logo  = $tenant->logo_path ?? null;
        $this->current_cover = $tenant->cover_path ?? null;        // ✅ NEW
    }

    protected function rules()
    {
        return [
            'name'  => 'required|string|max:255|regex:/^[a-zA-Z\s\-\.]+$/',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',

            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|max:255|confirmed',
            'new_password_confirmation' => 'required|string',

            'shop_name'         => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-\.]+$/',
            'shop_address'      => 'nullable|string|max:500',
            'shop_tagline'      => 'nullable|string|max:200',        // ✅ NEW
            'shop_introduction' => 'nullable|string|max:2000',       // ✅ NEW

            'new_logo'  => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
            'new_cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',   // ✅ NEW
        ];
    }

    // ============================================
    // PROFILE
    // ============================================
    public function updateProfile()
    {
        $this->validate([
            'name'  => 'required|string|max:255|regex:/^[a-zA-Z\s\-\.]+$/',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
        ]);

        $sanitized = [
            'name'  => $this->sanitizeInput($this->name),
            'email' => strtolower(trim($this->email)),
            'phone' => $this->sanitizeInput($this->phone),
        ];

        Auth::user()->update($sanitized);

        $this->name  = $sanitized['name'];
        $this->email = $sanitized['email'];
        $this->phone = $sanitized['phone'];

        session()->flash('success', 'Profile updated successfully!');
    }

    // ============================================
    // PASSWORD
    // ============================================
    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|max:255|confirmed',
            'new_password_confirmation' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make(trim($this->new_password))]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('success', 'Password updated successfully!');
    }

    // ============================================
    // SHOP
    // ============================================
    public function updateShop()
    {
        $this->validate([
            'shop_name'         => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-\.]+$/',
            'shop_address'      => 'nullable|string|max:500',
            'shop_tagline'      => 'nullable|string|max:200',       // ✅ NEW
            'shop_introduction' => 'nullable|string|max:2000',      // ✅ NEW
        ]);

        $sanitized = [
            'name'         => $this->sanitizeInput($this->shop_name),
            'address'      => $this->sanitizeInput($this->shop_address),
            'tagline'      => $this->sanitizeInput($this->shop_tagline),               // ✅ NEW
            'introduction' => $this->sanitizeMultiline($this->shop_introduction),      // ✅ NEW
        ];

        Auth::user()->tenant->update($sanitized);

        $this->shop_name         = $sanitized['name'];
        $this->shop_address      = $sanitized['address'];
        $this->shop_tagline      = $sanitized['tagline'];
        $this->shop_introduction = $sanitized['introduction'];

        session()->flash('success', 'Shop settings updated!');
    }

    // ============================================
    // LOGO
    // ============================================
    public function updateLogo()
    {
        $this->validate([
            'new_logo' => 'required|image|mimes:jpg,jpeg,png,webp,svg|max:2048',
        ]);

        $tenant = Auth::user()->tenant;
        $oldLogo = $tenant->logo_path;

        $path = $this->new_logo->store('tenant-logos/' . $tenant->id, 'public');
        $tenant->update(['logo_path' => $path]);

        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        $this->current_logo = $path;
        $this->new_logo = null;

        session()->flash('success', 'Shop logo updated successfully!');
    }

    public function removeLogo()
    {
        $tenant = Auth::user()->tenant;

        if ($tenant->logo_path && Storage::disk('public')->exists($tenant->logo_path)) {
            Storage::disk('public')->delete($tenant->logo_path);
        }

        $tenant->update(['logo_path' => null]);
        $this->current_logo = null;

        session()->flash('success', 'Shop logo removed successfully!');
    }

    // ============================================
    // ✅ NEW: COVER
    // ============================================
    public function updateCover()
    {
        $this->validate([
            'new_cover' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $tenant = Auth::user()->tenant;
        $oldCover = $tenant->cover_path;

        $path = $this->new_cover->store('tenant-covers/' . $tenant->id, 'public');
        $tenant->update(['cover_path' => $path]);

        if ($oldCover && Storage::disk('public')->exists($oldCover)) {
            Storage::disk('public')->delete($oldCover);
        }

        $this->current_cover = $path;
        $this->new_cover = null;

        session()->flash('success', 'Cover photo updated successfully!');
    }

    public function removeCover()
    {
        $tenant = Auth::user()->tenant;

        if ($tenant->cover_path && Storage::disk('public')->exists($tenant->cover_path)) {
            Storage::disk('public')->delete($tenant->cover_path);
        }

        $tenant->update(['cover_path' => null]);
        $this->current_cover = null;

        session()->flash('success', 'Cover photo removed successfully!');
    }

    // ============================================
    // NOTIFICATIONS
    // ============================================
    public function updateNotifications()
    {
        $this->notify_orders    = filter_var($this->notify_orders, FILTER_VALIDATE_BOOLEAN);
        $this->notify_low_stock = filter_var($this->notify_low_stock, FILTER_VALIDATE_BOOLEAN);
        $this->notify_reviews   = filter_var($this->notify_reviews, FILTER_VALIDATE_BOOLEAN);
        $this->notify_staff     = filter_var($this->notify_staff, FILTER_VALIDATE_BOOLEAN);

        session()->flash('success', 'Notification preferences saved!');
    }

    // ============================================
    // HELPERS
    // ============================================
    private function sanitizeInput($input)
    {
        if (empty($input)) return '';

        $input = strip_tags($input);
        $input = trim($input);
        $input = preg_replace('/\s+/', ' ', $input);
        $input = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $input);
        $input = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $input);

        return $input;
    }

    // ✅ NEW: preserves line breaks for long-form text
    private function sanitizeMultiline($input)
    {
        if (empty($input)) return '';

        $input = strip_tags($input);
        $input = trim($input);
        $input = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $input);
        $input = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $input);
        $input = preg_replace("/\n{3,}/", "\n\n", $input);

        return $input;
    }

    // ============================================
    // DANGER ZONE
    // ============================================
    public function deleteAllData()
    {
        session()->flash('error', 'This action requires administrator password confirmation. Please contact support.');
    }

    public function deactivateShop()
    {
        $tenant = Auth::user()->tenant;

        $hasActiveOrders = \App\Models\Order::where('tenant_id', $tenant->id)
            ->whereIn('status', ['pending', 'brewing', 'preparing', 'ready'])
            ->exists();

        if ($hasActiveOrders) {
            session()->flash('error', 'Cannot deactivate shop with active orders. Please complete all orders first.');
            return;
        }

        $tenant->update(['status' => 'inactive']);
        session()->flash('success', 'Shop deactivated successfully!');
    }

    public function render()
    {
        return view('pages.shop_owner.settings.settings');
    }
};
?>