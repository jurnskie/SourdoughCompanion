<?php

use App\Models\User;

test('horizon dashboard requires authentication', function () {
    $response = $this->get('/horizon');

    $response->assertRedirect('/login');
});

test('unauthorized user cannot access horizon dashboard', function () {
    $unauthorizedUser = User::factory()->create([
        'email' => 'unauthorized@example.com',
    ]);

    $response = $this->actingAs($unauthorizedUser)->get('/horizon');

    $response->assertStatus(403);
});

test('authorized user can access horizon dashboard', function () {
    $authorizedUser = User::factory()->create([
        'email' => 'jurnskie@gmail.com',
    ]);

    $response = $this->actingAs($authorizedUser)->get('/horizon');

    $response->assertStatus(200);
});

test('development user can access horizon dashboard', function () {
    $devUser = User::factory()->create([
        'email' => 'sourdough@localhost',
    ]);

    $response = $this->actingAs($devUser)->get('/horizon');

    $response->assertStatus(200);
});

test('horizon configuration includes authentication middleware', function () {
    $middleware = config('horizon.middleware');

    expect($middleware)->toContain('auth');
    expect($middleware)->toContain('web');
});

test('horizon gate handles null user gracefully', function () {
    expect(\Gate::allows('viewHorizon', null))->toBeFalse();
});
