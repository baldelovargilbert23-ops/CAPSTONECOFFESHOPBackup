<?php

use App\Models\Branch;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.shop_owner')] class extends Component
{
    use WithFileUploads;

    public string $search = '';
    public string $tab = 'active';
    public bool $showModal = false;
    public bool $showStaffModal = false;
    public bool $showBranchDetail = false;
    public ?int $branchId = null;
    public ?int $selectedBranchId = null;

    public int $currentStep = 1;
    public int $totalSteps = 4;

    public string $name = '';
    public string $block = '';
    public string $street = '';
    public string $barangay = '';
    public string $contact_number = '';
    public bool $is_active = true;

    public string $city = 'Victorias';
    public string $province = 'Negros Occidental';

    public array $victoriasBarangays = [
        'Barangay I (Pob.)', 'Barangay II (Pob.)', 'Barangay III (Pob.)',
        'Barangay IV (Pob.)', 'Barangay V (Pob.)', 'Barangay VI (Pob.)',
        'Barangay VI-A', 'Barangay VII (Pob.)', 'Barangay VIII (Pob.)',
        'Barangay IX (Daan Banwa)', 'Barangay X (Estado)', 'Barangay XI (Gawahon)',
        'Barangay XII', 'Barangay XIII (Gloryville)', 'Barangay XIV',
        'Barangay XV', 'Barangay XV-A', 'Barangay XVI', 'Barangay XVI-A',
        'Barangay XVII', 'Barangay XVIII', 'Barangay XVIII-A',
        'Barangay XIX', 'Barangay XIX-A (Canetown Subdivision)',
        'Barangay XX', 'Barangay XXI',
    ];

    public $business_permit_file;
    public $dti_sec_registration_file;
    public $bir_certificate_file;
    public $barangay_clearance_file;
    public $sanitary_permit_file;
    public $fire_safety_file;
    public $location_photo_file;

    public string $dti_sec_number = '';
    public string $bir_tin = '';
    public string $mayors_permit_number = '';

    public $manager_id_file;
    public string $manager_name = '';
    public string $manager_email = '';
    public string $manager_password = '';
    public string $manager_password_confirmation = '';

    public string $staff_name = '';
    public string $staff_email = '';
    public string $staff_password = '';
    public string $staff_role = 'cashier';
    public ?int $staff_branch_id = null;
    public ?int $staffId = null;

    public string $period = 'today';

    // ================================================================
    // RULES
    // ================================================================

    protected function rules(): array
    {
        $rules = [
            'name'           => 'required|string|min:3|max:255',
            'contact_number' => ['nullable', 'string', 'digits:11', 'regex:/^09[0-9]{9}$/'],
            'is_active'      => 'boolean',
        ];

        if (! $this->branchId) {
            $rules['barangay'] = ['required', 'string', Rule::in($this->victoriasBarangays)];
            $rules['block'] = 'nullable|string|max:100';
            $rules['street'] = 'nullable|string|max:150';

            $rules['business_permit_file'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['dti_sec_registration_file'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['bir_certificate_file'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['barangay_clearance_file'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['sanitary_permit_file'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['fire_safety_file'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['location_photo_file'] = 'nullable|file|mimes:jpg,jpeg,png|max:5120';

            $rules['manager_id_file'] = 'required|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['manager_name'] = 'required|string|min:3|max:255';
            $rules['manager_email'] = 'required|email:rfc,dns|max:255|unique:users,email';
            $rules['manager_password'] = 'required|string|min:8|max:72|same:manager_password_confirmation';
            $rules['manager_password_confirmation'] = 'required|string|min:8';

            $rules['dti_sec_number'] = ['required', 'string', 'min:7', 'max:8', 'regex:/^[0-9]+$/'];
            $rules['bir_tin'] = ['required', 'string', 'digits_between:9,12', 'regex:/^[0-9\-]+$/'];
            $rules['mayors_permit_number'] = ['required', 'string', 'max:100'];
        } else {
            $rules['barangay'] = ['nullable', 'string', Rule::in($this->victoriasBarangays)];
            $rules['block'] = 'nullable|string|max:100';
            $rules['street'] = 'nullable|string|max:150';
            $rules['business_permit_file'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['dti_sec_registration_file'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['bir_certificate_file'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['barangay_clearance_file'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['sanitary_permit_file'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['fire_safety_file'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
            $rules['location_photo_file'] = 'nullable|file|mimes:jpg,jpeg,png|max:5120';
            $rules['manager_id_file'] = 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120';
        }

        return $rules;
    }

    protected function stepRules(int $step): array
    {
        return match ($step) {
            1 => [
                'name' => 'required|string|min:3|max:255',
                'barangay' => ['required', 'string', Rule::in($this->victoriasBarangays)],
                'block' => 'nullable|string|max:100',
                'street' => 'nullable|string|max:150',
                'contact_number' => ['nullable', 'string', 'digits:11', 'regex:/^09[0-9]{9}$/'],
            ],
            2 => [
                'business_permit_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'dti_sec_registration_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'bir_certificate_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'barangay_clearance_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'sanitary_permit_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'fire_safety_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'location_photo_file' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
                'dti_sec_number' => ['required', 'string', 'min:7', 'max:8', 'regex:/^[0-9]+$/'],
                'bir_tin' => ['required', 'string', 'digits_between:9,12', 'regex:/^[0-9\-]+$/'],
                'mayors_permit_number' => ['required', 'string', 'max:100'],
            ],
            3 => [
                'manager_id_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'manager_name' => 'required|string|min:3|max:255',
                'manager_email' => 'required|email:rfc,dns|max:255|unique:users,email',
                'manager_password' => 'required|string|min:8|max:72|same:manager_password_confirmation',
                'manager_password_confirmation' => 'required|string|min:8',
            ],
            default => [],
        };
    }

    protected function staffRules(): array
    {
        $tenantId = Auth::user()->tenant_id;

        return [
            'staff_name' => 'required|string|min:3|max:255',
            'staff_email' => 'required|email:rfc,dns|max:255|unique:users,email,' . $this->staffId,
            'staff_password' => $this->staffId ? 'nullable|string|min:8|max:72' : 'required|string|min:8|max:72',
            'staff_role' => 'required|in:branch_manager,cashier,barista',
            'staff_branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true),
            ],
        ];
    }

    // ================================================================
    // MESSAGES
    // ================================================================

    protected function messages(): array
    {
        return [
            'name.required' => 'Branch name is required.',
            'name.min' => 'Branch name must be at least 3 characters.',
            'name.max' => 'Branch name is too long.',
            'barangay.required' => 'Barangay is required.',
            'barangay.in' => 'Please select a valid barangay from the list.',
            'contact_number.digits' => 'Contact number must be exactly 11 digits.',
            'contact_number.regex' => 'Contact number must start with "09".',
            'business_permit_file.required' => 'Business permit is required.',
            'dti_sec_registration_file.required' => 'DTI/SEC registration is required.',
            'bir_certificate_file.required' => 'BIR certificate is required.',
            'barangay_clearance_file.required' => 'Barangay clearance is required.',
            'sanitary_permit_file.required' => 'Sanitary permit is required.',
            'fire_safety_file.required' => 'Fire safety certificate is required.',
            'manager_id_file.required' => "Manager's valid ID is required.",
            'manager_name.required' => "Manager's name is required.",
            'manager_email.required' => "Manager's email is required.",
            'manager_email.unique' => 'This email is already registered.',
            'manager_password.required' => 'Password is required.',
            'manager_password.same' => 'Passwords do not match.',
            'manager_password_confirmation.required' => 'Please confirm the password.',
            'dti_sec_number.required' => 'DTI/SEC number is required.',
            'dti_sec_number.regex' => 'DTI/SEC number must contain numbers only.',
            'bir_tin.required' => 'BIR TIN is required.',
            'bir_tin.regex' => 'BIR TIN may only contain numbers and dashes.',
            'mayors_permit_number.required' => "Mayor's permit number is required.",
            'staff_name.required' => 'Staff name is required.',
            'staff_email.unique' => 'This email is already in use.',
            'staff_password.required' => 'Password is required.',
            'staff_role.required' => 'Please select a role.',
            'staff_branch_id.exists' => 'Selected branch is not available.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'business_permit_file' => 'business permit',
            'dti_sec_registration_file' => 'DTI/SEC registration',
            'bir_certificate_file' => 'BIR certificate',
            'barangay_clearance_file' => 'barangay clearance',
            'sanitary_permit_file' => 'sanitary permit',
            'fire_safety_file' => 'fire safety certificate',
            'manager_id_file' => "manager's valid ID",
            'manager_name' => "manager's name",
            'manager_email' => "manager's email",
            'dti_sec_number' => 'DTI/SEC number',
            'bir_tin' => 'BIR TIN',
            'mayors_permit_number' => "mayor's permit number",
            'staff_name' => 'staff name',
            'staff_email' => 'staff email',
            'staff_password' => 'password',
            'staff_role' => 'role',
            'staff_branch_id' => 'branch',
        ];
    }

    // ================================================================
    // SANITIZATION
    // ================================================================

    protected function prepareForValidation($attributes): array
    {
        $stringFields = ['name', 'block', 'street', 'barangay', 'manager_name', 'manager_email', 'staff_name', 'staff_email'];

        foreach ($stringFields as $field) {
            if (isset($attributes[$field]) && is_string($attributes[$field])) {
                $attributes[$field] = trim(strip_tags($attributes[$field]));
            }
        }

        if (! empty($attributes['contact_number'])) {
            $attributes['contact_number'] = preg_replace('/[^0-9]/', '', $attributes['contact_number']);
            if (strlen($attributes['contact_number']) === 12 && str_starts_with($attributes['contact_number'], '63')) {
                $attributes['contact_number'] = '0' . substr($attributes['contact_number'], 2);
            }
        }

        if (! empty($attributes['dti_sec_number'])) {
            $attributes['dti_sec_number'] = preg_replace('/[^0-9]/', '', $attributes['dti_sec_number']);
        }
        if (! empty($attributes['bir_tin'])) {
            $attributes['bir_tin'] = preg_replace('/[^0-9\-]/', '', $attributes['bir_tin']);
        }
        if (! empty($attributes['mayors_permit_number'])) {
            $attributes['mayors_permit_number'] = strtoupper(trim(strip_tags($attributes['mayors_permit_number'])));
        }
        if (! empty($attributes['manager_email'])) {
            $attributes['manager_email'] = strtolower($attributes['manager_email']);
        }
        if (! empty($attributes['staff_email'])) {
            $attributes['staff_email'] = strtolower($attributes['staff_email']);
        }

        return $attributes;
    }

    // ================================================================
    // WIZARD
    // ================================================================

    public function nextStep(): void
    {
        if ($this->branchId) {
            $this->save();
            return;
        }
        $this->validate($this->stepRules($this->currentStep));
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) $this->currentStep--;
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) $this->currentStep = $step;
    }

    // ================================================================
    // TABS & MODALS
    // ================================================================

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['active', 'archived'], true)) {
            $this->tab = $tab;
            $this->search = '';
        }
    }

    public function openModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->resetAll();

        if ($id) {
            $branch = Branch::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
            $this->branchId = $branch->id;
            $this->name = $branch->name;
            $this->barangay = $branch->barangay ?? '';
            $this->block = '';
            $this->street = $branch->address ?? '';
            $this->contact_number = $branch->contact_number ?? '';
            $this->dti_sec_number = $branch->dti_sec_number ?? '';
            $this->bir_tin = $branch->bir_tin ?? '';
            $this->mayors_permit_number = $branch->mayors_permit_number ?? '';
            $this->is_active = $branch->is_active;
        }

        $this->showModal = true;
    }

    private function resetAll(): void
    {
        $this->reset([
            'name', 'block', 'street', 'barangay',
            'contact_number', 'is_active', 'branchId',
            'manager_name', 'manager_email', 'manager_password', 'manager_password_confirmation',
            'business_permit_file', 'dti_sec_registration_file', 'bir_certificate_file',
            'barangay_clearance_file', 'sanitary_permit_file', 'fire_safety_file',
            'manager_id_file', 'location_photo_file',
            'dti_sec_number', 'bir_tin', 'mayors_permit_number',
        ]);
        $this->currentStep = 1;
        $this->is_active = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetAll();
        $this->resetValidation();
    }

    public function openStaffModal(?int $branchId = null, ?int $staffId = null): void
    {
        $this->resetValidation();
        $this->reset(['staff_name', 'staff_email', 'staff_password', 'staff_role', 'staff_branch_id', 'staffId']);

        if ($branchId) $this->staff_branch_id = $branchId;

        if ($staffId) {
            $staff = User::where('tenant_id', Auth::user()->tenant_id)
                ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
                ->findOrFail($staffId);
            $this->staffId = $staff->id;
            $this->staff_name = $staff->name;
            $this->staff_email = $staff->email;
            $this->staff_role = $staff->role;
            $this->staff_branch_id = $staff->branch_id;
        }

        $this->showStaffModal = true;
    }

    public function closeStaffModal(): void
    {
        $this->showStaffModal = false;
        $this->reset(['staff_name', 'staff_email', 'staff_password', 'staff_role', 'staff_branch_id', 'staffId']);
        $this->resetValidation();
    }

    public function viewBranchDetail($branchId): void
    {
        $this->selectedBranchId = $branchId;
        $this->showBranchDetail = true;
    }

    public function closeBranchDetail(): void
    {
        $this->showBranchDetail = false;
        $this->selectedBranchId = null;
        $this->period = 'today';
    }

    public function setPeriod($period): void
    {
        if (in_array($period, ['today', 'week', 'month', 'year'])) {
            $this->period = $period;
        }
    }

    // ================================================================
    // SAVE — BRANCH
    // ================================================================

    public function save(): void
    {
        $this->validate();

        $tenantId = Auth::user()->tenant_id;

        try {
            $branch = DB::transaction(function () use ($tenantId) {
                $fullAddress = collect([$this->block, $this->street])
                    ->filter(fn ($v) => $v !== null && $v !== '')
                    ->implode(', ');

                $data = [
                    'name' => $this->name,
                    'address' => $fullAddress ?: null,
                    'barangay' => $this->barangay ?: null,
                    'city' => $this->city,
                    'province' => $this->province,
                    'contact_number' => $this->contact_number ?: null,
                    'dti_sec_number' => $this->dti_sec_number ?: null,
                    'bir_tin' => $this->bir_tin ?: null,
                    'mayors_permit_number' => $this->mayors_permit_number ?: null,
                    'tenant_id' => $tenantId,
                ];

                if ($this->business_permit_file) {
                    $data['business_permit_path'] = $this->business_permit_file->store('branches/documents/business_permits', 'public');
                }
                if ($this->dti_sec_registration_file) {
                    $data['dti_sec_registration_path'] = $this->dti_sec_registration_file->store('branches/documents/dti_sec', 'public');
                }
                if ($this->bir_certificate_file) {
                    $data['bir_certificate_path'] = $this->bir_certificate_file->store('branches/documents/bir', 'public');
                }
                if ($this->barangay_clearance_file) {
                    $data['barangay_clearance_path'] = $this->barangay_clearance_file->store('branches/documents/barangay_clearances', 'public');
                }
                if ($this->sanitary_permit_file) {
                    $data['sanitary_permit_path'] = $this->sanitary_permit_file->store('branches/documents/sanitary_permits', 'public');
                }
                if ($this->fire_safety_file) {
                    $data['fire_safety_path'] = $this->fire_safety_file->store('branches/documents/fire_safety', 'public');
                }
                if ($this->manager_id_file) {
                    $data['manager_id_path'] = $this->manager_id_file->store('branches/documents/manager_ids', 'public');
                }
                if ($this->location_photo_file) {
                    $data['location_photo_path'] = $this->location_photo_file->store('branches/documents/location_photos', 'public');
                }

                if (! $this->branchId) {
                    // ✅ NEW BRANCH — requires admin approval
                    $data['approval_status'] = 'pending';
                    $data['is_active']       = false;
                    $data['created_by']      = Auth::id();
                } else {
                    // Editing existing branch — preserve approval, respect user's active toggle
                    $data['is_active'] = $this->is_active;
                }

                $attempts = 0;
                $branch = null;

                while (true) {
                    try {
                        $branch = Branch::updateOrCreate(
                            ['id' => $this->branchId, 'tenant_id' => $tenantId],
                            $data
                        );
                        break;
                    } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                        if ($this->branchId || ++$attempts >= 5) throw $e;
                        unset($data['code']);
                        $data['code'] = null;
                    }
                }

                if (! $this->branchId) {
                    $manager = User::create([
                        'tenant_id' => $tenantId,
                        'branch_id' => $branch->id,
                        'name' => $this->manager_name,
                        'email' => $this->manager_email,
                        'password' => Hash::make($this->manager_password),
                        'role' => 'branch_manager',
                        'is_active' => true,
                    ]);
                    $branch->update(['branch_manager_id' => $manager->id]);
                }

                return $branch;
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $this->addError('name', 'A branch code conflict occurred. Please try again.');
            return;
        }

        $isNew = ! $this->branchId;
        $this->closeModal();

        session()->flash('success', $isNew
            ? '✅ Branch submitted for admin approval. You\'ll be notified once it\'s approved.'
            : 'Branch updated successfully!');
    }

    // ================================================================
    // SAVE — STAFF
    // ================================================================

    public function saveStaff(): void
    {
        $validated = $this->validate($this->staffRules());
        $tenantId = Auth::user()->tenant_id;

        $userData = [
            'tenant_id' => $tenantId,
            'branch_id' => $validated['staff_branch_id'],
            'name' => $validated['staff_name'],
            'email' => $validated['staff_email'],
            'role' => $validated['staff_role'],
            'is_active' => true,
        ];

        if ($this->staffId) {
            $staff = User::where('tenant_id', $tenantId)
                ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
                ->findOrFail($this->staffId);

            if (! empty($validated['staff_password'])) {
                $userData['password'] = Hash::make($validated['staff_password']);
            }

            $staff->update($userData);
            $message = 'Staff updated successfully!';
        } else {
            $userData['password'] = Hash::make($validated['staff_password']);
            User::create($userData);
            $message = 'Staff added successfully!';
        }

        $this->closeStaffModal();
        session()->flash('success', $message);
    }

    // ================================================================
    // TOGGLE / ARCHIVE
    // ================================================================

    public function toggleStatus(int $id): void
    {
        $tenantId = Auth::user()->tenant_id;
        $branch = Branch::where('tenant_id', $tenantId)->findOrFail($id);

        if ($branch->is_active) {
            $activeCount = Branch::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count();

            if ($activeCount <= 1) {
                session()->flash('error', 'Cannot archive the last active branch.');
                return;
            }
        }

        $branch->update(['is_active' => ! $branch->is_active]);

        if ($branch->branch_manager_id) {
            User::where('tenant_id', $tenantId)
                ->where('id', $branch->branch_manager_id)
                ->update(['is_active' => $branch->is_active]);
        }

        session()->flash('success', $branch->is_active
            ? 'Branch restored!'
            : 'Branch archived!');
    }

    public function toggleStaffStatus(int $id): void
    {
        $tenantId = Auth::user()->tenant_id;

        $staff = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->findOrFail($id);

        $staff->update(['is_active' => ! $staff->is_active]);
        session()->flash('success', 'Staff status updated!');
    }

    // ================================================================
    // RENDER
    // ================================================================

    public function render()
    {
        $tenantId = Auth::user()->tenant_id;

        $branches = Branch::where('tenant_id', $tenantId)
            ->where('is_active', $this->tab === 'active')
            ->when($this->search, function ($query) {
                $search = trim(strip_tags($this->search));
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('code', 'like', '%' . $search . '%')
                      ->orWhere('address', 'like', '%' . $search . '%');
                });
            })
            ->withCount(['users as staff_count' => function ($query) {
                $query->whereIn('role', ['branch_manager', 'cashier', 'barista'])
                    ->where('is_active', true);
            }])
            ->latest()
            ->get();

        $activeCount = Branch::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $archivedCount = Branch::where('tenant_id', $tenantId)->where('is_active', false)->count();

        $branchStats = [];
        foreach ($branches as $branch) {
            $branchStats[$branch->id] = $this->getBranchStats($branch->id);
        }

        $staff = User::where('tenant_id', $tenantId)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->where('is_active', true)
            ->whereNotNull('branch_id')
            ->whereHas('branch', fn ($q) => $q->where('is_active', true))
            ->with('branch')
            ->latest()
            ->get();

        $selectedBranch = null;
        $branchRevenue = 0;
        $branchOrders = 0;
        $branchRating = 0;
        $branchStaff = [];
        $recentOrders = [];
        $recentReviews = [];

        if ($this->selectedBranchId) {
            $selectedBranch = Branch::where('tenant_id', $tenantId)
                ->withCount(['users as staff_count' => function ($query) {
                    $query->whereIn('role', ['branch_manager', 'cashier', 'barista']);
                }])
                ->find($this->selectedBranchId);

            if ($selectedBranch) {
                $branchRevenue = Order::where('tenant_id', $tenantId)
                    ->where('branch_id', $this->selectedBranchId)
                    ->where('status', 'completed')
                    ->when($this->period === 'today', fn ($q) => $q->whereDate('created_at', today()))
                    ->when($this->period === 'week', fn ($q) => $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->when($this->period === 'month', fn ($q) => $q->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
                    ->when($this->period === 'year', fn ($q) => $q->whereYear('created_at', now()->year))
                    ->sum('total_amount');

                $branchOrders = Order::where('tenant_id', $tenantId)
                    ->where('branch_id', $this->selectedBranchId)
                    ->count();

                $branchRating = Review::where('tenant_id', $tenantId)
                    ->where('branch_id', $this->selectedBranchId)
                    ->avg('rating') ?? 0;

                $branchStaff = User::where('tenant_id', $tenantId)
                    ->where('branch_id', $this->selectedBranchId)
                    ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
                    ->get();

                $recentOrders = Order::where('tenant_id', $tenantId)
                    ->where('branch_id', $this->selectedBranchId)
                    ->latest()->take(5)->get();

                $recentReviews = Review::where('tenant_id', $tenantId)
                    ->where('branch_id', $this->selectedBranchId)
                    ->with('user')->latest()->take(5)->get();
            }
        }

        $assignableBranches = Branch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('pages.shop_owner.branches.branches', [
            'branches' => $branches,
            'staff' => $staff,
            'branchStats' => $branchStats,
            'activeCount' => $activeCount,
            'archivedCount' => $archivedCount,
            'assignableBranches' => $assignableBranches,
            'selectedBranch' => $selectedBranch,
            'branchRevenue' => $branchRevenue,
            'branchOrders' => $branchOrders,
            'branchRating' => $branchRating,
            'branchStaff' => $branchStaff,
            'recentOrders' => $recentOrders,
            'recentReviews' => $recentReviews,
        ]);
    }

    private function getBranchStats($branchId)
    {
        $tenantId = Auth::user()->tenant_id;

        $totalRevenue = Order::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)->where('status', 'completed')
            ->sum('total_amount');

        $totalOrders = Order::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)->count();

        $averageRating = Review::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)->avg('rating') ?? 0;

        $staffCount = User::where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->whereIn('role', ['branch_manager', 'cashier', 'barista'])
            ->where('is_active', true)->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'average_rating' => number_format($averageRating, 1),
            'staff_count' => $staffCount,
        ];
    }
};
?>
<div class="branches-root">
    @once
    <style>
        :root {
            --bg-primary: #f5f0e8;
            --bg-secondary: #faf6ef;
            --bg-elevated: #ffffff;
            --bg-inner: #f5f0e8;
            --text-primary: #1a3a3a;
            --text-muted: #6b7f7a;
            --accent: #c8965f;
            --accent-warm: #d4a574;
            --accent-bright: #b8874a;
            --forest: #1a3a3a;
            --border-soft: rgba(26, 58, 58, 0.10);
            --ok: #16a34a;
            --warn: #d97706;
            --bad: #dc2626;
            --info: #2563eb;
            --violet: #7c3aed;
        }
        .dark {
            --bg-primary: #0a1f1a;
            --bg-secondary: #0f2922;
            --bg-elevated: #14332c;
            --bg-inner: #0a1f1a;
            --text-primary: #f0ebe0;
            --text-muted: #8a9a94;
            --accent: #d4a574;
            --accent-warm: #e0b585;
            --accent-bright: #c8965f;
            --forest: #f0ebe0;
            --border-soft: rgba(212, 165, 116, 0.12);
            --ok: #4ade80;
            --warn: #fbbf24;
            --bad: #f87171;
            --info: #60a5fa;
            --violet: #a78bfa;
        }
        .branches-root { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; }
        .branches-root .card { background-color: var(--bg-elevated); border: 1px solid var(--border-soft); border-radius: 1rem; }
        .branches-root input, .branches-root select, .branches-root textarea {
            background-color: var(--bg-inner); color: var(--text-primary);
            border: 1px solid var(--border-soft);
        }
        .branches-root input::placeholder, .branches-root textarea::placeholder { color: var(--text-muted); }
        .branches-root input:focus, .branches-root select:focus, .branches-root textarea:focus {
            border-color: var(--accent); outline: none;
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 20%, transparent);
        }
    </style>
    @endonce

    <main class="relative min-h-screen font-sans p-4 sm:p-6 space-y-6 transition-colors duration-300"
          style="background-color: var(--bg-primary); color: var(--text-primary);">
        <div class="max-w-7xl mx-auto space-y-6">

            @if(session('success'))
                <div class="px-4 py-3 rounded-xl flex items-center justify-between"
                     style="background-color: color-mix(in srgb, var(--ok) 10%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 30%, transparent); color: var(--ok);">
                    <span><i class="fa fa-check-circle mr-2"></i> {{ session('success') }}</span>
                    <button onclick="this.parentElement.style.display='none'">×</button>
                </div>
            @endif
            @if(session('error'))
                <div class="px-4 py-3 rounded-xl flex items-center justify-between"
                     style="background-color: color-mix(in srgb, var(--bad) 10%, transparent); border: 1px solid color-mix(in srgb, var(--bad) 30%, transparent); color: var(--bad);">
                    <span><i class="fa fa-exclamation-circle mr-2"></i> {{ session('error') }}</span>
                    <button onclick="this.parentElement.style.display='none'">×</button>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                             style="background-color: var(--forest); color: var(--bg-primary);">
                            <i class="fa fa-code-branch text-lg"></i>
                        </div>
                        <h1 class="text-2xl font-black" style="color: var(--text-primary);">Branch Management</h1>
                    </div>
                    <p class="text-sm mt-1 ml-1" style="color: var(--text-muted);">Manage extra locations and staff members</p>
                </div>
                <button wire:click="openModal"
                        class="px-4 py-2.5 font-bold rounded-xl transition text-sm flex items-center gap-2"
                        style="background-color: var(--accent); color: var(--bg-primary);">
                    <i class="fa fa-plus"></i> Add New Branch
                </button>
            </div>

            <div class="flex items-center gap-2 border-b" style="border-color: var(--border-soft);">
                <button wire:click="setTab('active')" class="px-4 py-2 text-sm font-bold transition relative"
                        style="color: {{ $tab === 'active' ? 'var(--accent)' : 'var(--text-muted)' }};">
                    <i class="fa fa-store mr-1"></i> Active
                    <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold"
                          style="background-color: {{ $tab === 'active' ? 'color-mix(in srgb, var(--accent) 15%, transparent)' : 'var(--bg-inner)' }};">
                        {{ $activeCount }}
                    </span>
                    @if($tab === 'active')
                        <span class="absolute bottom-0 left-0 right-0 h-0.5" style="background-color: var(--accent);"></span>
                    @endif
                </button>
                <button wire:click="setTab('archived')" class="px-4 py-2 text-sm font-bold transition relative"
                        style="color: {{ $tab === 'archived' ? 'var(--accent)' : 'var(--text-muted)' }};">
                    <i class="fa fa-box-archive mr-1"></i> Archived
                    <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold"
                          style="background-color: {{ $tab === 'archived' ? 'color-mix(in srgb, var(--accent) 15%, transparent)' : 'var(--bg-inner)' }};">
                        {{ $archivedCount }}
                    </span>
                    @if($tab === 'archived')
                        <span class="absolute bottom-0 left-0 right-0 h-0.5" style="background-color: var(--accent);"></span>
                    @endif
                </button>
            </div>

            <div class="card p-4 shadow-sm">
                <div class="relative">
                    <i class="fa fa-search absolute left-4 top-1/2 -translate-y-1/2 text-xs" style="color: var(--text-muted);"></i>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search branches..."
                           class="w-full text-sm rounded-xl pl-10 pr-4 py-2.5 outline-none transition">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($branches as $branch)
                    @php $stats = $branchStats[$branch->id] ?? ['total_revenue' => 0, 'total_orders' => 0, 'average_rating' => 0, 'staff_count' => 0]; @endphp
                    <div wire:click="viewBranchDetail({{ $branch->id }})"
                         class="card p-5 flex flex-col justify-between space-y-4 cursor-pointer group transition shadow-sm hover:shadow-lg {{ $branch->is_active ? '' : 'opacity-60' }}">
                        <div>
                            <div class="flex items-start justify-between gap-2 mb-2">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-bold text-lg" style="color: var(--text-primary);">{{ $branch->name }}</h3>
                                        @if(! $branch->branch_manager_id)
                                            <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase"
                                                  style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); color: var(--warn);">
                                                No Manager
                                            </span>
                                        @endif
                                    </div>
                                    @if($branch->code)
                                        <span class="text-[10px] font-mono font-semibold" style="color: var(--accent);">{{ $branch->code }}</span>
                                    @endif
                                </div>

                                {{-- ✅ THREE-STATE STATUS BADGE --}}
                                @php
                                    $badge = match ($branch->approval_status) {
                                        'approved' => ['label' => 'Approved',         'color' => 'var(--ok)'],
                                        'pending'  => ['label' => 'Pending Approval', 'color' => 'var(--warn)'],
                                        'rejected' => ['label' => 'Rejected',         'color' => 'var(--bad)'],
                                        default    => ['label' => ucfirst($branch->approval_status), 'color' => 'var(--text-muted)'],
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase flex-shrink-0"
                                      style="background-color: color-mix(in srgb, {{ $badge['color'] }} 10%, transparent);
                                             color: {{ $badge['color'] }};">
                                    {{ $badge['label'] }}
                                </span>
                            </div>

                            @if($branch->address || $branch->barangay || $branch->city)
                                <p class="text-xs flex items-start gap-2 mb-1.5 mt-2" style="color: var(--text-muted);">
                                    <i class="fa fa-location-dot mt-0.5" style="color: var(--accent);"></i>
                                    <span>
                                        {{ $branch->address }}@if($branch->address && $branch->barangay), @endif{{ $branch->barangay }}@if($branch->barangay && $branch->city), @endif{{ $branch->city }}@if($branch->city && $branch->province), @endif{{ $branch->province }}
                                    </span>
                                </p>
                            @endif

                            <div class="grid grid-cols-3 gap-2 mt-3 pt-3" style="border-top: 1px solid var(--border-soft);">
                                <div class="text-center">
                                    <p class="text-xs font-bold" style="color: var(--accent);">₱{{ number_format($stats['total_revenue'], 0) }}</p>
                                    <p class="text-[8px] uppercase" style="color: var(--text-muted);">Revenue</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-bold" style="color: var(--text-primary);">{{ $stats['total_orders'] }}</p>
                                    <p class="text-[8px] uppercase" style="color: var(--text-muted);">Orders</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-bold" style="color: var(--accent);">{{ $stats['average_rating'] }} ★</p>
                                    <p class="text-[8px] uppercase" style="color: var(--text-muted);">Rating</p>
                                </div>
                            </div>

                            <p class="text-xs flex items-center gap-2 mt-2" style="color: var(--text-muted);">
                                <i class="fa fa-users" style="color: var(--accent);"></i>
                                <span>{{ $stats['staff_count'] }} staff member(s)</span>
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 flex-wrap" style="border-top: 1px solid var(--border-soft);">
                            <button wire:click.stop="openModal({{ $branch->id }})"
                                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition"
                                    style="color: var(--text-muted);">
                                <i class="fa fa-pen mr-1"></i> Edit
                            </button>
                            <button wire:click.stop="toggleStatus({{ $branch->id }})"
                                    wire:confirm="{{ $branch->is_active ? 'Archive this branch?' : 'Restore this branch?' }}"
                                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition"
                                    style="color: {{ $branch->is_active ? 'var(--warn)' : 'var(--ok)' }};">
                                <i class="fa {{ $branch->is_active ? 'fa-box-archive' : 'fa-rotate-left' }} mr-1"></i>
                                {{ $branch->is_active ? 'Archive' : 'Restore' }}
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full card p-12 text-center shadow-sm">
                        @if($tab === 'archived')
                            <i class="fa fa-box-archive text-4xl mb-3 opacity-30"></i>
                            <p class="text-sm font-semibold" style="color: var(--text-muted);">No archived branches</p>
                        @else
                            <i class="fa fa-store text-4xl mb-3 opacity-30"></i>
                            <p class="text-sm font-semibold" style="color: var(--text-muted);">No branches found</p>
                            <button wire:click="openModal"
                                    class="mt-4 px-4 py-2 text-sm font-bold rounded-lg transition"
                                    style="background-color: var(--accent); color: var(--bg-primary);">
                                <i class="fa fa-plus mr-2"></i> Create Your First Branch
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>

            {{-- STAFF TABLE --}}
            <div class="card overflow-hidden shadow-sm">
                <div class="px-6 py-4" style="border-bottom: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                    <h2 class="text-lg font-black flex items-center gap-2" style="color: var(--text-primary);">
                        <i class="fa fa-users" style="color: var(--accent);"></i> Branch Staff ({{ $staff->count() }})
                    </h2>
                    <p class="text-xs mt-1" style="color: var(--text-muted);">Staff assigned to extra branches. Use the Staff page to manage staff at your main shop.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[800px]">
                        <thead style="background-color: var(--bg-inner);">
                            <tr class="text-xs font-bold uppercase" style="color: var(--text-muted);">
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Email</th>
                                <th class="px-6 py-3">Role</th>
                                <th class="px-6 py-3">Branch</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staff as $member)
                                <tr style="border-top: 1px solid var(--border-soft);">
                                    <td class="px-6 py-3 font-medium" style="color: var(--text-primary);">{{ $member->name }}</td>
                                    <td class="px-6 py-3" style="color: var(--text-muted);">{{ $member->email }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase"
                                              style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent);">
                                            {{ str_replace('_', ' ', $member->role) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3" style="color: var(--text-muted);">{{ $member->branch->name ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase"
                                              style="background-color: color-mix(in srgb, {{ $member->is_active ? 'var(--ok)' : 'var(--text-muted)' }} 10%, transparent);
                                                     color: {{ $member->is_active ? 'var(--ok)' : 'var(--text-muted)' }};">
                                            {{ $member->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-2">
                                            <button wire:click="openStaffModal(null, {{ $member->id }})" class="text-xs" style="color: var(--accent);">Edit</button>
                                            <span style="color: var(--border-soft);">|</span>
                                            <button wire:click="toggleStaffStatus({{ $member->id }})" class="text-xs"
                                                    style="color: {{ $member->is_active ? 'var(--bad)' : 'var(--ok)' }};">
                                                {{ $member->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center" style="color: var(--text-muted);">
                                        <i class="fa fa-users text-3xl mb-2 block opacity-50"></i>
                                        No branch staff yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- BRANCH MODAL — 4-STEP WIZARD --}}
        @if($showModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(0,0,0,0.6);">
                <div class="branches-root card w-full max-w-lg shadow-2xl max-h-[92vh] overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between px-6 pt-6 pb-4 flex-shrink-0" style="border-bottom: 1px solid var(--border-soft);">
                        <h3 class="text-lg font-black flex items-center gap-2" style="color: var(--text-primary);">
                            <i class="fa fa-store" style="color: var(--accent);"></i>
                            {{ $branchId ? 'Edit Branch' : 'Add New Branch' }}
                        </h3>
                        <button wire:click="closeModal" style="color: var(--text-muted);"><i class="fa fa-times"></i></button>
                    </div>

                    @if(!$branchId)
                        <div class="px-6 pt-5 pb-2 flex-shrink-0">
                            <div class="flex items-center justify-between">
                                @for($i = 1; $i <= $totalSteps; $i++)
                                    @php
                                        $labels = [1 => 'Branch', 2 => 'Documents', 3 => 'Manager', 4 => 'Review'];
                                        $isComplete = $i < $currentStep;
                                        $isActive = $i === $currentStep;
                                    @endphp
                                    <div class="flex-1 flex items-center {{ $i < $totalSteps ? '' : 'flex-none' }}">
                                        <div class="flex flex-col items-center gap-1 flex-shrink-0">
                                            <button type="button" wire:click="goToStep({{ $i }})" @if($i >= $currentStep) disabled @endif
                                                    class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                                                    style="background-color: {{ $isComplete ? 'var(--ok)' : ($isActive ? 'var(--accent)' : 'var(--bg-inner)') }};
                                                           color: {{ $isComplete || $isActive ? 'var(--bg-primary)' : 'var(--text-muted)' }};">
                                                @if($isComplete) <i class="fa fa-check"></i> @else {{ $i }} @endif
                                            </button>
                                            <span class="text-[9px] font-bold uppercase" style="color: {{ $isActive ? 'var(--accent)' : ($isComplete ? 'var(--ok)' : 'var(--text-muted)') }};">
                                                {{ $labels[$i] }}
                                            </span>
                                        </div>
                                        @if($i < $totalSteps)
                                            <div class="flex-1 h-0.5 mx-2 rounded-full" style="background-color: {{ $i < $currentStep ? 'var(--ok)' : 'var(--border-soft)' }};"></div>
                                        @endif
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endif

                    <form wire:submit.prevent="{{ $branchId ? 'save' : 'nextStep' }}" class="flex flex-col flex-grow overflow-hidden">
                        <div class="px-6 py-4 space-y-3 overflow-y-auto flex-grow">
                            {{-- STEP 1 --}}
                            @if($branchId || $currentStep === 1)
                                <div class="rounded-xl p-4 space-y-3" style="background-color: var(--bg-inner); border: 1px solid var(--border-soft);">
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Branch Name *</label>
                                        <input wire:model="name" type="text" placeholder="e.g. Downtown Branch"
                                               class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                        @error('name') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Barangay *</label>
                                        <select wire:model="barangay" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                            <option value="">Select Barangay</option>
                                            @foreach($victoriasBarangays as $bgy)
                                                <option value="{{ $bgy }}">{{ $bgy }}</option>
                                            @endforeach
                                        </select>
                                        @error('barangay') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Block/Purok</label>
                                            <input wire:model="block" type="text" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Street</label>
                                            <input wire:model="street" type="text" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Contact Number</label>
                                        <input wire:model="contact_number" type="text" placeholder="09123456789" maxlength="11"
                                               class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                        @error('contact_number') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex items-center gap-2 pt-1">
                                        <input wire:model="is_active" type="checkbox" id="is_active" class="rounded" style="accent-color: var(--accent);">
                                        <label for="is_active" class="text-xs font-semibold" style="color: var(--text-primary);">Active</label>
                                    </div>
                                </div>
                            @endif

                            {{-- STEP 2 --}}
                            @if(!$branchId && $currentStep === 2)
                                <div class="space-y-3">
                                    <p class="text-xs font-bold" style="color: var(--text-primary);">Business Numbers</p>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">DTI/SEC Number *</label>
                                            <input wire:model="dti_sec_number" type="text" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                            @error('dti_sec_number') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">BIR TIN *</label>
                                            <input wire:model="bir_tin" type="text" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                            @error('bir_tin') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Mayor's Permit Number *</label>
                                        <input wire:model="mayors_permit_number" type="text" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                        @error('mayors_permit_number') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>

                                    <p class="text-xs font-bold mt-4" style="color: var(--text-primary);">Required Documents</p>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Mayor's/Business Permit *</label>
                                        <input wire:model="business_permit_file" type="file" accept="image/*,application/pdf" class="w-full text-xs rounded-xl px-3 py-2">
                                        @error('business_permit_file') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">DTI/SEC Registration *</label>
                                        <input wire:model="dti_sec_registration_file" type="file" accept="image/*,application/pdf" class="w-full text-xs rounded-xl px-3 py-2">
                                        @error('dti_sec_registration_file') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">BIR Certificate *</label>
                                        <input wire:model="bir_certificate_file" type="file" accept="image/*,application/pdf" class="w-full text-xs rounded-xl px-3 py-2">
                                        @error('bir_certificate_file') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Barangay Clearance *</label>
                                        <input wire:model="barangay_clearance_file" type="file" accept="image/*,application/pdf" class="w-full text-xs rounded-xl px-3 py-2">
                                        @error('barangay_clearance_file') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Sanitary Permit *</label>
                                        <input wire:model="sanitary_permit_file" type="file" accept="image/*,application/pdf" class="w-full text-xs rounded-xl px-3 py-2">
                                        @error('sanitary_permit_file') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Fire Safety Certificate *</label>
                                        <input wire:model="fire_safety_file" type="file" accept="image/*,application/pdf" class="w-full text-xs rounded-xl px-3 py-2">
                                        @error('fire_safety_file') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Location Photo (Optional)</label>
                                        <input wire:model="location_photo_file" type="file" accept="image/*" class="w-full text-xs rounded-xl px-3 py-2">
                                    </div>
                                </div>
                            @endif

                            {{-- STEP 3 --}}
                            @if(!$branchId && $currentStep === 3)
                                <div class="space-y-3">
                                    <p class="text-xs font-bold" style="color: var(--text-primary);">Branch Manager Account</p>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Manager's Valid ID *</label>
                                        <input wire:model="manager_id_file" type="file" accept="image/*,application/pdf" class="w-full text-xs rounded-xl px-3 py-2">
                                        @error('manager_id_file') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Full Name *</label>
                                        <input wire:model="manager_name" type="text" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                        @error('manager_name') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Email *</label>
                                        <input wire:model="manager_email" type="email" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                        @error('manager_email') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Password *</label>
                                        <input wire:model="manager_password" type="password" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                        @error('manager_password') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Confirm Password *</label>
                                        <input wire:model="manager_password_confirmation" type="password" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                        @error('manager_password_confirmation') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            @endif

                            {{-- STEP 4 --}}
                            @if(!$branchId && $currentStep === 4)
                                <div class="rounded-xl p-4" style="background-color: color-mix(in srgb, var(--ok) 5%, transparent); border: 1px solid color-mix(in srgb, var(--ok) 20%, transparent);">
                                    <p class="text-sm font-bold mb-3" style="color: var(--text-primary);">Review Your Branch</p>
                                    <div class="space-y-2 text-xs">
                                        <div class="flex justify-between py-1.5" style="border-bottom: 1px solid var(--border-soft);">
                                            <span style="color: var(--text-muted);">Branch Name</span>
                                            <span class="font-bold" style="color: var(--text-primary);">{{ $name ?: '—' }}</span>
                                        </div>
                                        <div class="flex justify-between py-1.5" style="border-bottom: 1px solid var(--border-soft);">
                                            <span style="color: var(--text-muted);">Location</span>
                                            <span class="font-bold text-right" style="color: var(--text-primary);">
                                                {{ $block }}{{ $block && $street ? ', ' : '' }}{{ $street }}{{ ($block || $street) ? ', ' : '' }}{{ $barangay }}, Victorias
                                            </span>
                                        </div>
                                        <div class="flex justify-between py-1.5" style="border-bottom: 1px solid var(--border-soft);">
                                            <span style="color: var(--text-muted);">Documents</span>
                                            <span class="font-bold" style="color: var(--ok);"><i class="fa fa-check-circle mr-1"></i> All Attached</span>
                                        </div>
                                        <div class="flex justify-between py-1.5">
                                            <span style="color: var(--text-muted);">Manager</span>
                                            <span class="font-bold" style="color: var(--violet);">{{ $manager_name ?: '—' }}</span>
                                        </div>
                                    </div>

                                    <div class="mt-4 p-3 rounded-xl" style="background-color: color-mix(in srgb, var(--warn) 10%, transparent); border: 1px solid color-mix(in srgb, var(--warn) 30%, transparent);">
                                        <p class="text-[11px] font-semibold" style="color: var(--warn);">
                                            <i class="fa fa-info-circle mr-1"></i>
                                            Your branch will be submitted for admin approval. It won't be visible to customers until approved.
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="px-6 py-4 flex items-center justify-between gap-2 flex-shrink-0" style="border-top: 1px solid var(--border-soft); background-color: var(--bg-inner);">
                            <div>
                                @if(!$branchId && $currentStep > 1)
                                    <button type="button" wire:click="previousStep" class="px-4 py-2 text-xs font-bold" style="color: var(--text-muted);">
                                        <i class="fa fa-arrow-left text-[10px]"></i> Back
                                    </button>
                                @else
                                    <button type="button" wire:click="closeModal" class="px-4 py-2 text-xs font-bold" style="color: var(--text-muted);">Cancel</button>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                @if(!$branchId && $currentStep < $totalSteps)
                                    <span class="text-[10px]" style="color: var(--text-muted);">Step {{ $currentStep }} of {{ $totalSteps }}</span>
                                    <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl" style="background-color: var(--accent); color: var(--bg-primary);">
                                        Next <i class="fa fa-arrow-right text-[10px]"></i>
                                    </button>
                                @elseif(!$branchId && $currentStep === $totalSteps)
                                    <button type="button" wire:click="save" wire:loading.attr="disabled"
                                            class="px-5 py-2 text-xs font-bold rounded-xl disabled:opacity-50"
                                            style="background-color: var(--ok); color: var(--bg-primary);">
                                        <span wire:loading.remove wire:target="save"><i class="fa fa-paper-plane mr-1"></i> Submit for Approval</span>
                                        <span wire:loading wire:target="save"><i class="fa fa-spinner fa-spin mr-1"></i> Submitting...</span>
                                    </button>
                                @else
                                    <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl" style="background-color: var(--accent); color: var(--bg-primary);">Update Branch</button>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- STAFF MODAL --}}
        @if($showStaffModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(0,0,0,0.6);">
                <div class="branches-root card w-full max-w-md p-6 space-y-4 shadow-2xl">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-black flex items-center gap-2" style="color: var(--text-primary);">
                            <i class="fa fa-user-tie" style="color: var(--accent);"></i>
                            {{ $staffId ? 'Edit Staff' : 'Add New Staff' }}
                        </h3>
                        <button wire:click="closeStaffModal" style="color: var(--text-muted);"><i class="fa fa-times"></i></button>
                    </div>
                    <form wire:submit.prevent="saveStaff" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Full Name *</label>
                            <input wire:model="staff_name" type="text" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                            @error('staff_name') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Email *</label>
                            <input wire:model="staff_email" type="email" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                            @error('staff_email') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">{{ $staffId ? 'New Password (Optional)' : 'Password *' }}</label>
                            <input wire:model="staff_password" type="password" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                            @error('staff_password') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Role *</label>
                            <select wire:model="staff_role" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                <option value="branch_manager">Branch Manager</option>
                                <option value="cashier">Cashier</option>
                                <option value="barista">Barista</option>
                            </select>
                            @error('staff_role') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase mb-1" style="color: var(--text-muted);">Assign to Branch</label>
                            <select wire:model="staff_branch_id" class="w-full text-sm rounded-xl px-3 py-2 outline-none">
                                <option value="">— Tenant's Own Store —</option>
                                @foreach($assignableBranches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] mt-1" style="color: var(--text-muted);">Leave as "Own Store" to assign this staff to your main shop.</p>
                            @error('staff_branch_id') <p class="text-[10px] mt-1" style="color: var(--bad);">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex justify-end gap-2 pt-4" style="border-top: 1px solid var(--border-soft);">
                            <button type="button" wire:click="closeStaffModal" class="px-4 py-2 text-xs font-bold" style="color: var(--text-muted);">Cancel</button>
                            <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl" style="background-color: var(--accent); color: var(--bg-primary);">
                                {{ $staffId ? 'Update' : 'Add' }} Staff
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- BRANCH DETAIL MODAL --}}
        @if($showBranchDetail && $selectedBranch)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto" style="background-color: rgba(0,0,0,0.6);">
                <div class="branches-root card w-full max-w-4xl p-6 space-y-4 shadow-2xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-4 flex-wrap gap-2" style="border-bottom: 1px solid var(--border-soft);">
                        <div>
                            <h3 class="text-xl font-black flex items-center gap-2" style="color: var(--text-primary);">
                                <i class="fa fa-store" style="color: var(--accent);"></i> {{ $selectedBranch->name }}
                                @if(! $selectedBranch->is_active)
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-bold uppercase"
                                          style="background-color: color-mix(in srgb, var(--text-muted) 10%, transparent); color: var(--text-muted);">Archived</span>
                                @endif
                            </h3>
                            <p class="text-xs" style="color: var(--text-muted);">
                                @if($selectedBranch->code){{ $selectedBranch->code }} •@endif
                                {{ $selectedBranch->barangay ?? 'No barangay' }}, {{ $selectedBranch->city ?? 'Victorias' }}
                            </p>
                        </div>
                        <button wire:click="closeBranchDetail" style="color: var(--text-muted);"><i class="fa fa-times text-xl"></i></button>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        @foreach(['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
                            <button wire:click="setPeriod('{{ $key }}')"
                                    class="px-3 py-1.5 text-xs font-bold rounded-lg"
                                    style="{{ $period === $key ? 'background-color: var(--accent); color: var(--bg-primary);' : 'background-color: var(--bg-inner); color: var(--text-muted);' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="rounded-xl p-4 text-center" style="background-color: color-mix(in srgb, var(--accent) 10%, transparent);">
                            <p class="text-2xl font-black" style="color: var(--accent);">₱{{ number_format($branchRevenue, 2) }}</p>
                            <p class="text-[10px] uppercase" style="color: var(--text-muted);">Revenue</p>
                        </div>
                        <div class="rounded-xl p-4 text-center" style="background-color: color-mix(in srgb, var(--info) 10%, transparent);">
                            <p class="text-2xl font-black" style="color: var(--info);">{{ $branchOrders }}</p>
                            <p class="text-[10px] uppercase" style="color: var(--text-muted);">Total Orders</p>
                        </div>
                        <div class="rounded-xl p-4 text-center" style="background-color: color-mix(in srgb, var(--accent) 10%, transparent);">
                            <p class="text-2xl font-black" style="color: var(--accent);">{{ number_format($branchRating, 1) }} ★</p>
                            <p class="text-[10px] uppercase" style="color: var(--text-muted);">Average Rating</p>
                        </div>
                        <div class="rounded-xl p-4 text-center" style="background-color: color-mix(in srgb, var(--ok) 10%, transparent);">
                            <p class="text-2xl font-black" style="color: var(--ok);">{{ $branchStaff->count() }}</p>
                            <p class="text-[10px] uppercase" style="color: var(--text-muted);">Staff Members</p>
                        </div>
                    </div>

                    @if($branchStaff->count() > 0)
                        <div>
                            <h4 class="font-bold text-sm mb-2" style="color: var(--text-primary);"><i class="fa fa-users" style="color: var(--accent);"></i> Staff Members</h4>
                            <div class="space-y-2">
                                @foreach($branchStaff as $staffMember)
                                    <div class="flex items-center justify-between rounded-lg px-4 py-2" style="background-color: var(--bg-inner);">
                                        <div>
                                            <p class="font-medium text-sm" style="color: var(--text-primary);">{{ $staffMember->name }}</p>
                                            <p class="text-xs" style="color: var(--text-muted);">{{ $staffMember->email }}</p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold uppercase"
                                              style="background-color: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent);">
                                            {{ str_replace('_', ' ', $staffMember->role) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($recentOrders->count() > 0)
                        <div>
                            <h4 class="font-bold text-sm mb-2" style="color: var(--text-primary);"><i class="fa fa-shopping-cart" style="color: var(--accent);"></i> Recent Orders</h4>
                            <div class="space-y-2">
                                @foreach($recentOrders as $order)
                                    <div class="flex items-center justify-between rounded-lg px-4 py-2" style="background-color: var(--bg-inner);">
                                        <div>
                                            <p class="font-mono text-xs font-bold" style="color: var(--accent);">{{ $order->order_number ?? '#' . $order->id }}</p>
                                            <p class="text-[10px]" style="color: var(--text-muted);">{{ $order->created_at->format('M d, Y h:i A') }}</p>
                                        </div>
                                        <p class="font-bold text-sm" style="color: var(--text-primary);">₱{{ number_format($order->total_amount, 2) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($recentReviews->count() > 0)
                        <div>
                            <h4 class="font-bold text-sm mb-2" style="color: var(--text-primary);"><i class="fa fa-star" style="color: var(--accent);"></i> Recent Reviews</h4>
                            <div class="space-y-2">
                                @foreach($recentReviews as $review)
                                    <div class="rounded-lg px-4 py-2" style="background-color: var(--bg-inner);">
                                        <div class="flex items-center justify-between">
                                            <p class="font-medium text-sm" style="color: var(--text-primary);">{{ $review->user->name ?? 'Anonymous' }}</p>
                                            <p class="text-[10px]" style="color: var(--text-muted);">{{ $review->created_at->diffForHumans() }}</p>
                                        </div>
                                        @if($review->comment)
                                            <p class="text-xs mt-1" style="color: var(--text-muted);">"{{ $review->comment }}"</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end pt-4" style="border-top: 1px solid var(--border-soft);">
                        <button wire:click="closeBranchDetail" class="px-4 py-2 text-xs font-bold" style="color: var(--text-muted);">Close</button>
                    </div>
                </div>
            </div>
        @endif
    </main>
</div>