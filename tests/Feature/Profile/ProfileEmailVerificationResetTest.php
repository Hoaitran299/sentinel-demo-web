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
            'phone' => '0911111111',
        ]);

        $this->assertNotNull($user->email_verified_at);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Changed Email User',
            'email' => 'changed@example.com',
            'phone' => '0922222222',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('changed@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_keeping_same_email_preserves_email_verified_at(): void
    {
        $user = User::factory()->create([
            'email' => 'same@example.com',
            'phone' => '0933333333',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Same Email User',
            'email' => 'same@example.com',
            'phone' => '0933333333',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
