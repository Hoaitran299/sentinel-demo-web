<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_render_the_profile_form(): void
    {
        $dangerousAddress = '<script>alert("profile")</script>';
        $user = User::factory()->create([
            'name' => 'Demo Profile',
            'email' => 'profile@example.com',
            'phone' => '0912345678',
            'address' => $dangerousAddress,
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response
            ->assertOk()
            ->assertSee('Demo Profile')
            ->assertSee('profile@example.com')
            ->assertSee('0912345678')
            ->assertSee($dangerousAddress)
            ->assertDontSee($dangerousAddress, false)
            ->assertSee('name="_token"', false);
    }

    public function test_authenticated_user_can_update_an_allowlisted_profile_payload(): void
    {
        $user = User::factory()->create([
            'password' => 'original-password',
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'phone' => '0987654321',
            'address' => 'Updated address',
            'password' => 'attacker-controlled-password',
        ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'profile-updated');

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertSame('0987654321', $user->phone);
        $this->assertSame('Updated address', $user->address);
        $this->assertTrue(Hash::check('original-password', $user->password));
    }
}
