<?php

use App\Models\GlobalThemeSettings;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.admin')] class extends Component
{
    public array $colors = [];
    public bool $storageReady = false;
    public bool $previewDark = false;

    public function mount(): void
    {
        $this->storageReady = Schema::hasTable('global_theme_settings');
        $this->colors = GlobalThemeSettings::currentValues();
    }

    protected function rules(): array
    {
        $rules = [];

        foreach (array_keys(GlobalThemeSettings::defaults()) as $color) {
            $rules['colors.' . $color] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        }

        return $rules;
    }

    public function saveTheme(): void
    {
        if (!$this->storageReady) {
            $this->addError('storage', 'Theme storage is not ready yet. Apply the pending database migration, then reload this page.');
            return;
        }

        $validated = $this->validate();

        GlobalThemeSettings::query()->updateOrCreate(
            ['id' => 1],
            $validated['colors']
        );
        GlobalThemeSettings::clearCurrentValuesCache();

        session()->flash('success', 'The theme is now applied across the site.');
    }

    public function resetDraft(): void
    {
        $this->colors = GlobalThemeSettings::defaults();
    }

    public function togglePreviewMode(): void
    {
        $this->previewDark = !$this->previewDark;
    }
};
