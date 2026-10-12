<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ReviewRepliedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Review $review,
        public ?string $replierName = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $productName = $this->review->product?->name ?? 'your review';
        $branchName  = $this->review->branch?->name ?? 'Branch';
        $shopName    = $this->review->tenant?->name ?? 'Shop';

        $snippet = $this->review->owner_reply
            ? Str::limit($this->review->owner_reply, 80)
            : '';

        return [
            'title'        => "💬 {$branchName} replied to your review",
            'message'      => "\"{$snippet}\"",
            'url'          => route('customer.reviews') . '?highlight=' . $this->review->id,
            'color'        => 'emerald',
            'icon'         => 'chat',
            'review_id'    => $this->review->id,
            'product_name' => $productName,
            'shop_name'    => $shopName,
            'branch_name'  => $branchName,
            'replier_name' => $this->replierName,
            'type'         => 'review_reply',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}