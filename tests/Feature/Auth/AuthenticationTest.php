<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('shows the Arabic login form to guests', function () {
    $response = $this->get(route('login'));

    $response
        ->assertOk()
        ->assertSee('تسجيل الدخول')
        ->assertSee('name="_token"', false)
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false);
});

it('authenticates an active user and redirects to the dashboard', function () {
    $user = User::factory()->create([
        'email' => 'active@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $response = $this->post(route('login.store'), [
        'email' => ' Active@Example.com ',
        'password' => 'secret-password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials with an Arabic validation message', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => 'user@example.com',
        'password' => 'wrong-password',
    ]);

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'بيانات تسجيل الدخول غير صحيحة.']);
    $this->assertGuest();
});

it('rejects inactive accounts even when their credentials are correct', function () {
    User::factory()->inactive()->create([
        'email' => 'inactive@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => 'inactive@example.com',
        'password' => 'secret-password',
    ]);

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'هذا الحساب غير نشط. راجع مدير النظام.']);
    $this->assertGuest();
});

it('rate limits repeated failed login attempts', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'email' => 'limited@example.com',
            'password' => "wrong-password-{$attempt}",
        ])->assertSessionHasErrors('email');
    }

    $response = $this->post(route('login.store'), [
        'email' => 'limited@example.com',
        'password' => 'wrong-password-6',
    ]);

    $response->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('محاولات تسجيل دخول كثيرة');
    $this->assertGuest();
});

it('logs an authenticated user out and invalidates the session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'تم تسجيل الخروج بنجاح.');
    $this->assertGuest();
});

it('ends an existing session after the account is deactivated', function () {
    $user = User::factory()->inactive()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'هذا الحساب غير نشط. راجع مدير النظام.']);
    $this->assertGuest();
});
