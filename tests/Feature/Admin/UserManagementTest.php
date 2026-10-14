<?php

use App\Enums\PermissionEnum;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('allows an administrator to list users and escapes their names', function () {
    $administrator = User::factory()->administrator()->create();
    User::factory()->create([
        'name' => '<script>alert("unsafe")</script>',
        'email' => 'listed@example.com',
    ]);

    $response = $this->actingAs($administrator)->get(route('admin.users.index'));

    $response
        ->assertOk()
        ->assertSee('listed@example.com')
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert("unsafe")</script>', false);
});

it('forbids a regular user from every user management action', function (string $routeName, array $parameters) {
    $regularUser = User::factory()->create();
    $targetUser = User::factory()->create();
    $resolvedParameters = $parameters === ['target'] ? [$targetUser] : [];

    $response = $this->actingAs($regularUser)->get(route($routeName, $resolvedParameters));

    $response->assertForbidden();
})->with([
    'list users' => ['admin.users.index', []],
    'create user' => ['admin.users.create', []],
    'edit user' => ['admin.users.edit', ['target']],
]);

it('allows an administrator to create an active account with a selected role', function () {
    $administrator = User::factory()->administrator()->create();

    $response = $this->actingAs($administrator)->post(route('admin.users.create'), [
        'name' => 'مستخدم جديد',
        'email' => ' New.User@Example.com ',
        'password' => 'strong-password',
        'password_confirmation' => 'strong-password',
        'role' => UserRole::USER->value,
        'is_active' => '1',
        'email_verified_at' => now()->toDateTimeString(),
    ]);

    $response
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('status', 'تم إنشاء المستخدم بنجاح.');

    $createdUser = User::query()->where('email', 'new.user@example.com')->firstOrFail();

    expect($createdUser->name)->toBe('مستخدم جديد')
        ->and($createdUser->role)->toBe(UserRole::USER)
        ->and($createdUser->is_active)->toBeTrue()
        ->and($createdUser->email_verified_at)->toBeNull()
        ->and(Hash::check('strong-password', $createdUser->password))->toBeTrue();
});

it('validates the user form with Arabic messages', function () {
    $administrator = User::factory()->administrator()->create();

    $response = $this->actingAs($administrator)->post(route('admin.users.create'), [
        'name' => '',
        'email' => 'invalid-email',
        'password' => 'short',
        'password_confirmation' => 'different',
        'role' => 'owner',
        'is_active' => 'yes',
    ]);

    $response
        ->assertSessionHasErrors(['name', 'email', 'password', 'role', 'is_active'])
        ->assertSessionHasErrors([
            'email' => 'يجب إدخال بريد إلكتروني صحيح.',
        ]);
});

it('updates account details without replacing an omitted password', function () {
    $administrator = User::factory()->administrator()->create();
    $user = User::factory()->create([
        'password' => Hash::make('current-password'),
    ]);

    $response = $this->actingAs($administrator)->post(route('admin.users.edit', $user), [
        'name' => 'مدير إضافي',
        'email' => 'second.admin@example.com',
        'password' => '',
        'password_confirmation' => '',
        'role' => UserRole::ADMINISTRATOR->value,
        'is_active' => '0',
    ]);

    $response
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('status', 'تم تحديث المستخدم بنجاح.');

    $user->refresh();

    expect($user->name)->toBe('مدير إضافي')
        ->and($user->email)->toBe('second.admin@example.com')
        ->and($user->role)->toBe(UserRole::ADMINISTRATOR)
        ->and($user->is_active)->toBeFalse()
        ->and(Hash::check('current-password', $user->password))->toBeTrue();
});

it('defines administrator permissions and project ownership access rules', function () {
    $administrator = User::factory()->administrator()->create();
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $inactiveAdministrator = User::factory()->administrator()->inactive()->create();

    foreach (PermissionEnum::cases() as $permission) {
        expect($administrator->can($permission->value))->toBeTrue()
            ->and($owner->can($permission->value))->toBeFalse()
            ->and($inactiveAdministrator->can($permission->value))->toBeFalse();
    }

    expect($owner->canAccessProjectOwnedBy($owner->id))->toBeTrue()
        ->and($otherUser->canAccessProjectOwnedBy($owner->id))->toBeFalse()
        ->and($administrator->canAccessProjectOwnedBy($owner->id))->toBeTrue()
        ->and($inactiveAdministrator->canAccessProjectOwnedBy($owner->id))->toBeFalse();
});
