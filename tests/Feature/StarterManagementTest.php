<?php

use App\Models\Starter;
use App\Models\User;
use App\Services\StarterService;
use Livewire\Volt\Volt;

test('starter service can delete starter with notifications cleanup', function () {
    $user = User::factory()->create(['email' => 'sourdough@localhost']);

    $starterService = app(StarterService::class);
    $starter = $starterService->createStarter('Test Starter', 'whole wheat');

    expect($user->starters()->count())->toBe(1);

    $result = $starterService->deleteStarter($starter);

    expect($result)->toBeTrue();
    expect($user->starters()->count())->toBe(0);
});

test('starter service can clear all notifications for user', function () {
    $user = User::factory()->create(['email' => 'sourdough@localhost']);

    $starterService = app(StarterService::class);
    $clearedCount = $starterService->clearAllNotifications($user);

    expect($clearedCount)->toBeInt();
});

test('starters list component can create new starter', function () {
    $user = User::factory()->create(['email' => 'sourdough@localhost']);

    Volt::actingAs($user)->test('starters-list')
        ->set('newStarterName', 'Test Starter')
        ->set('newStarterFlourType', 'rye')
        ->call('createStarter')
        ->assertHasNoErrors();

    expect(Starter::where('name', 'Test Starter')->exists())->toBeTrue();
});

test('starters list component can edit starter', function () {
    $user = User::factory()->create(['email' => 'sourdough@localhost']);
    $starterService = app(StarterService::class);
    $starter = $starterService->createStarter('Original Name', 'whole wheat');

    // First call editStarter to load the starter into editing mode
    $component = Volt::actingAs($user)->test('starters-list')
        ->call('editStarter', $starter->id);

    // Then set the individual properties
    $component->set('editingStarter', [
        'id' => $starter->id,
        'name' => 'Updated Name',
        'flour_type' => 'rye',
    ])
        ->call('updateStarter')
        ->assertHasNoErrors();

    $starter->refresh();
    expect($starter->name)->toBe('Updated Name');
    expect($starter->flour_type)->toBe('rye');
});

test('starters list component can delete starter', function () {
    $user = User::factory()->create(['email' => 'sourdough@localhost']);
    $starterService = app(StarterService::class);
    $starter = $starterService->createStarter('To Delete', 'whole wheat');

    expect(Starter::find($starter->id))->not->toBeNull();

    Volt::actingAs($user)->test('starters-list')
        ->call('confirmDelete', $starter->id)
        ->call('deleteStarter')
        ->assertHasNoErrors();

    expect(Starter::find($starter->id))->toBeNull();
});

test('starters list component can clear all notifications', function () {
    $user = User::factory()->create(['email' => 'sourdough@localhost']);

    Volt::actingAs($user)->test('starters-list')
        ->call('clearAllNotifications')
        ->assertHasNoErrors();
});

test('starter page displays starters list component', function () {
    $user = User::factory()->create(['email' => 'sourdough@localhost']);

    $response = $this->actingAs($user)->get('/starter');

    $response->assertStatus(200);
    $response->assertSeeLivewire('starters-list');
});

test('can delete the last remaining starter', function () {
    $user = User::factory()->create(['email' => 'sourdough@localhost']);
    $starterService = app(StarterService::class);

    // Create only one starter
    $starter = $starterService->createStarter('Last Starter', 'whole wheat');

    expect($user->starters()->count())->toBe(1);

    // Test deletion through the component
    Volt::actingAs($user)->test('starters-list')
        ->call('confirmDelete', $starter->id)
        ->assertSet('showDeleteConfirm', $starter->id)
        ->call('deleteStarter')
        ->assertHasNoErrors();

    expect(Starter::find($starter->id))->toBeNull();
    expect($user->starters()->count())->toBe(0);
});
