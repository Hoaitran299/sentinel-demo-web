<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProfileEmailVerificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_changing_email_resets_email_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Regression User',
            'email' => 'new@example.com',
            'phone' => '0911111111',
            'address' => 'Regression address',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_is_kept_when_email_is_unchanged(): void
    {
        $user = User::factory()->create([
            'email' => 'same@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Regression User',
            'email' => 'same@example.com',
            'phone' => '0911111111',
            'address' => 'Regression address',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
