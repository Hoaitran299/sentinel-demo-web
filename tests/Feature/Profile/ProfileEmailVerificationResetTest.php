<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProfileEmailVerificationResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_changing_email_resets_email_verified_at(): void
    {
        $user = User::factory()->create([
            'email' => 'old-address@example.com',
            'phone' => '0912345678',
            'address' => 'Original address',
            'email_verified_at' => now(),
        ]);

        $this->assertNotNull($user->email_verified_at);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'new-address@example.com',
            'phone' => $user->phone,
            'address' => $user->address,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-updated');

        $user->refresh();

        $this->assertSame('new-address@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_keeping_same_email_preserves_email_verified_at(): void
    {
        $user = User::factory()->create([
            'email' => 'same-address@example.com',
            'phone' => '0912345678',
            'address' => 'Original address',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'same-address@example.com',
            'phone' => $user->phone,
            'address' => $user->address,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-updated');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
