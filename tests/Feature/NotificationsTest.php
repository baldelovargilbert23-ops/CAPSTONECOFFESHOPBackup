<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SystemAnnouncement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcement_reaches_every_active_role_as_plain_text(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $owner = User::factory()->create(['role' => 'owner']);
        $branchManager = User::factory()->create(['role' => 'branch_manager']);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $barista = User::factory()->create(['role' => 'barista']);
        $inactive = User::factory()->create(['role' => 'customer', 'is_active' => false]);

        $this->assertFalse($inactive->fresh()->is_active);
        $activeUsers = User::query()->where('is_active', true)->get();
        $this->assertGreaterThanOrEqual(6, $activeUsers->count());

        Notification::fake();

        Livewire::actingAs($admin)
            ->test('pages::admin.announcements')
            ->set('title', '<b>Store update</b>')
            ->set('message', '<script>alert(1)</script>We close at 5.')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSee("Announcement sent to {$activeUsers->count()} active accounts.");

        foreach ($activeUsers as $recipient) {
            Notification::assertSentTo(
                $recipient,
                SystemAnnouncement::class,
                fn (SystemAnnouncement $notification): bool =>
                    $notification->title === 'Store update'
                    && $notification->message === 'alert(1)We close at 5.'
            );
        }

        Notification::assertNotSentTo($inactive, SystemAnnouncement::class);
    }

    public function test_notification_inbox_is_authenticated_and_each_user_can_only_manage_their_own_items(): void
    {
        $this->get('/notifications')->assertRedirect('/login');

        $recipient = User::factory()->create(['role' => 'owner']);
        $otherUser = User::factory()->create(['role' => 'customer']);
        $recipient->notifyNow(new SystemAnnouncement('Inventory update', 'Stock was reviewed.'));
        $otherUser->notifyNow(new SystemAnnouncement('Private update', 'This belongs to another user.'));
        $otherNotificationId = $otherUser->notifications()->firstOrFail()->id;
        $recipientNotification = $recipient->notifications()->firstOrFail();

        Livewire::actingAs($recipient)
            ->test('pages::notifications.index')
            ->assertSee('Inventory update')
            ->assertDontSee('Private update')
            ->call('markAsRead', $otherNotificationId)
            ->assertHasNoErrors()
            ->call('markAsRead', $recipientNotification->id)
            ->assertHasNoErrors();

        $this->assertNotNull($recipientNotification->fresh()->read_at);
        $this->assertNull($otherUser->notifications()->firstOrFail()->read_at);
    }
}
