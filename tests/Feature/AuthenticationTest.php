<?php

use App\Models\User;

test('login page is accessible', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Login');
});

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('starter'));
    $this->assertAuthenticatedAs($user);
});

test('user cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('user can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/login');
    $this->assertGuest();
});

test('protected routes require authentication', function () {
    $response = $this->get('/starter');

    $response->assertRedirect('/login');
});

test('authenticated users can access protected routes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/starter');

    $response->assertStatus(200);
});

test('remember me functionality works', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
        'remember' => '1',
    ]);

    $response->assertRedirect(route('starter'));
    $this->assertAuthenticatedAs($user);

    // Check that remember token is set
    expect($user->fresh()->remember_token)->not->toBeNull();
});
