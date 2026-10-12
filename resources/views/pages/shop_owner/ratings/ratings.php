<?php

use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.shop_owner')] class extends Component
{
    public $search = '';
    public $ratingFilter = null;
    public $replyingTo = null;
    public $replyContent = '';

    public function toggleStatus($id)
    {
        $review = Review::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        $review->update(['is_visible' => !$review->is_visible]);
        session()->flash('success', 'Review visibility updated!');
    }

    public function setReply($id)
    {
        $this->replyingTo = $id;
        $review = Review::where('tenant_id', Auth::user()->tenant_id)->find($id);
        $this->replyContent = $review?->owner_reply ?? '';
    }

    public function saveReply()
    {
        $review = Review::where('tenant_id', Auth::user()->tenant_id)->findOrFail($this->replyingTo);
        $review->update(['owner_reply' => $this->replyContent]);
        $this->replyingTo = null;
        $this->replyContent = '';
        session()->flash('success', 'Reply saved successfully!');
    }

    public function render()
    {
        $tenantId = Auth::user()->tenant_id ?? session('tenant_id');

        $stats = [
            'total'   => Review::where('tenant_id', $tenantId)->count(),
            'average' => round(Review::where('tenant_id', $tenantId)->avg('rating'), 1) ?: 0,
            'hidden'  => Review::where('tenant_id', $tenantId)->where('is_visible', false)->count(),
        ];

        $reviews = Review::where('tenant_id', $tenantId)
            ->with(['product', 'user'])
            ->when($this->ratingFilter, fn($q) => $q->where('rating', $this->ratingFilter))
            ->when($this->search, fn($q) => $q->whereHas('product', fn($p) => $p->where('name', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(10);

        return view('pages.shop_owner.ratings.ratings', [
            'reviews' => $reviews,
            'stats'   => $stats,
        ]);
    }
};
?>