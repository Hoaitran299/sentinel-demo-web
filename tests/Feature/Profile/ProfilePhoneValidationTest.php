<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProfilePhoneValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_empty_phone_is_rejected_with_validation_error(): void
    {
        $user = User::factory()->create(['phone' => '0912345678']);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'Updated Name',
                'email' => $user->email,
                'phone' => '',
                'address' => 'Some address',
            ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('phone');

        $user->refresh();

        $this->assertSame('0912345678', $user->phone);
    }

    public function test_missing_phone_is_rejected_without_query_exception(): void
    {
        $this->withoutExceptionHandling([ValidationException::class]);

        $user = User::factory()->create(['phone' => '0912345678']);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'Updated Name',
                'email' => $user->email,
                'address' => 'Some address',
            ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('phone');

        $user->refresh();

        $this->assertSame('0912345678', $user->phone);
    }

    public function test_phone_longer_than_twenty_characters_is_rejected(): void
    {
        $user = User::factory()->create(['phone' => '0912345678']);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'Updated Name',
                'email' => $user->email,
                'phone' => str_repeat('1', 21),
                'address' => 'Some address',
            ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('phone');

        $user->refresh();

        $this->assertSame('0912345678', $user->phone);
    }
}
