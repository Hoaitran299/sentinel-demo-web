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
            'email' => 'original@example.com',
            'email_verified_at' => now(),
        ]);

        $this->assertNotNull($user->email_verified_at);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Verified User',
            'email' => 'changed@example.com',
            'phone' => '0912345678',
            'address' => 'Some address',
        ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-updated');

        $user->refresh();

        $this->assertSame('changed@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_keeping_same_email_preserves_email_verified_at(): void
    {
        $user = User::factory()->create([
            'email' => 'same@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Verified User',
            'email' => 'same@example.com',
            'phone' => '0912345678',
            'address' => 'Some address',
        ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-updated');

        $user->refresh();

        $this->assertSame('same@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
    }
}
