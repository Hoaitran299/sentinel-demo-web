<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProfilePhoneValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_profile_update_rejects_empty_phone(): void
    {
        $user = User::factory()->create(['phone' => '0911111111']);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'X',
                'email' => $user->email,
                'phone' => '',
                'address' => null,
            ]);

        $response
            ->assertSessionHasErrors('phone')
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('0911111111', $user->phone);
    }

    public function test_profile_update_rejects_missing_phone(): void
    {
        $user = User::factory()->create(['phone' => '0922222222']);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'X',
                'email' => $user->email,
                'address' => null,
            ]);

        $response
            ->assertSessionHasErrors('phone')
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('0922222222', $user->phone);
    }

    public function test_profile_update_rejects_phone_longer_than_20_chars(): void
    {
        $user = User::factory()->create(['phone' => '0933333333']);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'X',
                'email' => $user->email,
                'phone' => str_repeat('1', 21),
                'address' => null,
            ]);

        $response
            ->assertSessionHasErrors('phone')
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('0933333333', $user->phone);
    }
}
