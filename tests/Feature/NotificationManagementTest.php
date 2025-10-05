<?php

use App\Models\User;
use App\Services\NotificationSchedulerService;
use App\Services\StarterService;
use Livewire\Volt\Volt;

test('notifications page is accessible to authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/notifications');

    $response->assertStatus(200);
    $response->assertSeeLivewire('notifications-manager');
});

test('notifications page requires authentication', function () {
    $response = $this->get('/notifications');

    $response->assertRedirect('/login');
});

test('notifications manager component loads without errors', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)->test('notifications-manager')
        ->assertHasNoErrors();
});

test('can filter notifications by type', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)->test('notifications-manager')
        ->set('filterType', 'FeedingReminderNotification')
        ->assertHasNoErrors()
        ->assertSet('filterType', 'FeedingReminderNotification');
});

test('can search notifications', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)->test('notifications-manager')
        ->set('search', 'test starter')
        ->assertHasNoErrors()
        ->assertSet('search', 'test starter');
});

test('can select and clear notification selections', function () {
    $user = User::factory()->create();

    $component = Volt::actingAs($user)->test('notifications-manager');

    // Test select all
    $component->call('selectAllNotifications')
        ->assertHasNoErrors();

    // Test clear selection
    $component->call('clearSelection')
        ->assertHasNoErrors()
        ->assertSet('selectedNotifications', []);
});

test('can cleanup orphaned notifications', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)->test('notifications-manager')
        ->call('cleanupOrphaned')
        ->assertHasNoErrors();
});

test('can clear all notifications', function () {
    $user = User::factory()->create();

    Volt::actingAs($user)->test('notifications-manager')
        ->call('clearAllNotifications')
        ->assertHasNoErrors();
});

test('notification scheduler service can get user notifications', function () {
    $user = User::factory()->create();
    $starterService = app(StarterService::class);

    // This should not throw an error even with no notifications
    $notifications = $starterService->getUserNotifications($user);

    expect($notifications)->toBeArray();
});

test('notification scheduler service can delete specific notification', function () {
    $notificationService = app(NotificationSchedulerService::class);

    // Should handle non-existent job ID gracefully
    $result = $notificationService->deleteNotification(99999);

    expect($result)->toBeFalse();
});

test('notification scheduler service can delete multiple notifications', function () {
    $notificationService = app(NotificationSchedulerService::class);

    // Should handle empty array
    $result = $notificationService->deleteNotifications([]);

    expect($result)->toBe(0);

    // Should handle non-existent job IDs
    $result = $notificationService->deleteNotifications([99999, 99998]);

    expect($result)->toBe(0);
});

test('notification scheduler service can update notification schedule', function () {
    $notificationService = app(NotificationSchedulerService::class);
    $newTime = now()->addHours(2);

    // Should handle non-existent job ID gracefully
    $result = $notificationService->updateNotificationSchedule(99999, $newTime);

    expect($result)->toBeFalse();
});

test('notification scheduler service can cleanup orphaned notifications', function () {
    $notificationService = app(NotificationSchedulerService::class);

    // Should run without errors
    $result = $notificationService->cleanupOrphanedNotifications();

    expect($result)->toBeInt();
    expect($result)->toBeGreaterThanOrEqual(0);
});

test('starter service provides notification management methods', function () {
    $user = User::factory()->create();
    $starterService = app(StarterService::class);

    // Test getUserNotifications
    $notifications = $starterService->getUserNotifications($user);
    expect($notifications)->toBeArray();

    // Test deleteNotification with non-existent ID
    $result = $starterService->deleteNotification(99999);
    expect($result)->toBeFalse();

    // Test deleteNotifications with empty array
    $result = $starterService->deleteNotifications([]);
    expect($result)->toBe(0);

    // Test updateNotificationSchedule with non-existent ID
    $result = $starterService->updateNotificationSchedule(99999, now()->addHour());
    expect($result)->toBeFalse();

    // Test cleanupOrphanedNotifications
    $result = $starterService->cleanupOrphanedNotifications();
    expect($result)->toBeInt();
    expect($result)->toBeGreaterThanOrEqual(0);

    // Test clearAllNotifications
    $result = $starterService->clearAllNotifications($user);
    expect($result)->toBeInt();
    expect($result)->toBeGreaterThanOrEqual(0);
});

test('cleanup orphaned notifications command runs successfully', function () {
    $this->artisan('notifications:cleanup-orphaned')
        ->assertExitCode(0);
});
