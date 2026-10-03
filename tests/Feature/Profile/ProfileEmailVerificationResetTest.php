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
            'phone' => '0911111111',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => 'changed@example.com',
            'phone' => $user->phone,
            'address' => 'Addr',
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
            'phone' => '0922222222',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Another Name',
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => 'Addr',
        ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-updated');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
