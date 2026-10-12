<?php

namespace App\Livewire\Customer;

use App\Models\Review;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.customer')] class extends Component
{
    #[Url(history: true, as: 'rating')]
    public $ratingFilter = null;

    #[Url(history: true, as: 'filter')]
    public $replyFilter = 'all';

    #[Url(history: true, as: 'sort')]
    public $sortBy = 'newest';

    public $search = '';

    public $showEditModal = false;
    public $editReviewId = null;
    public $edit_rating = 5;
    public $edit_comment = '';
    public $edit_product_name = '';

    public $deletingReviewId = null;

    public function setRatingFilter($rating)
    {
        $this->ratingFilter = ($this->ratingFilter == $rating) ? null : $rating;
    }

    public function setReplyFilter($filter)
    {
        $this->replyFilter = $filter;
    }

    public function clearFilters()
    {
        $this->ratingFilter = null;
        $this->replyFilter = 'all';
        $this->search = '';
    }

    public function openEditModal($reviewId)
    {
        $review = Review::where('id', $reviewId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$review) {
            session()->flash('error', 'Review not found.');
            return;
        }

        $this->editReviewId = $review->id;
        $this->edit_rating = $review->rating;
        $this->edit_comment = $review->comment;
        $this->edit_product_name = $review->product->name ?? 'Product';
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editReviewId = null;
        $this->edit_rating = 5;
        $this->edit_comment = '';
    }

    public function updateReview()
    {
        $this->validate([
            'edit_rating' => 'required|integer|min:1|max:5',
            'edit_comment' => 'nullable|string|max:500',
        ]);

        $review = Review::where('id', $this->editReviewId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$review) {
            session()->flash('error', 'Review not found.');
            $this->closeEditModal();
            return;
        }

        $review->update([
            'rating' => $this->edit_rating,
            'comment' => $this->edit_comment,
        ]);

        $this->closeEditModal();
        session()->flash('success', 'Review updated successfully!');
    }

    public function confirmDelete($reviewId)
    {
        $this->deletingReviewId = $reviewId;
    }

    public function cancelDelete()
    {
        $this->deletingReviewId = null;
    }

    public function deleteReview()
    {
        $review = Review::where('id', $this->deletingReviewId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$review) {
            session()->flash('error', 'Review not found.');
            $this->cancelDelete();
            return;
        }

        $review->delete();
        $this->cancelDelete();
        session()->flash('success', 'Review deleted successfully.');
    }

    public function render()
    {
        $baseQuery = Review::where('user_id', Auth::id())
            ->where('is_visible', true)
            ->with(['product', 'tenant']);

        // Stats — one query
        $stats = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(AVG(rating), 0) as average')
            ->selectRaw('SUM(CASE WHEN owner_reply IS NOT NULL AND owner_reply != "" THEN 1 ELSE 0 END) as with_replies')
            ->first();

        $stats = [
            'total' => (int) ($stats->total ?? 0),
            'average' => (float) ($stats->average ?? 0),
            'withReplies' => (int) ($stats->with_replies ?? 0),
            'awaitingReply' => ((int) ($stats->total ?? 0)) - ((int) ($stats->with_replies ?? 0)),
        ];

        // Distribution — one query
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $counts = (clone $baseQuery)
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        foreach ($counts as $star => $count) {
            if (isset($distribution[$star])) {
                $distribution[$star] = (int) $count;
            }
        }

        // Filtered reviews
        $reviews = (clone $baseQuery)
            ->when($this->ratingFilter, fn($q) => $q->where('rating', $this->ratingFilter))
            ->when($this->replyFilter === 'replied', fn($q) => $q->whereNotNull('owner_reply')->where('owner_reply', '!=', ''))
            ->when($this->replyFilter === 'awaiting', fn($q) => $q->where(fn($q2) => $q2->whereNull('owner_reply')->orWhere('owner_reply', '')))
            ->when($this->search, fn($q) => $q->where(function ($sub) {
                $sub->whereHas('product', fn($p) => $p->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('tenant', fn($t) => $t->where('name', 'like', "%{$this->search}%"))
                    ->orWhere('comment', 'like', "%{$this->search}%");
            }));

        $reviews = match ($this->sortBy) {
            'oldest' => $reviews->oldest(),
            'highest' => $reviews->orderByDesc('rating'),
            'lowest' => $reviews->orderBy('rating'),
            default => $reviews->latest(),
        };

        $reviews = $reviews->get();

        return view('pages.customer.⚡reviews.reviews', [
            'reviews' => $reviews,
            'stats' => $stats,
            'distribution' => $distribution,
        ]);
    }
};