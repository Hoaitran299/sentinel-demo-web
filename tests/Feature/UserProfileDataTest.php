<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProfileDataTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_factory_creates_a_user_with_valid_profile_data(): void
    {
        $user = User::factory()->create();

        $this->assertNotSame('', $user->phone);
        $this->assertLessThanOrEqual(20, mb_strlen($user->phone));
        $this->assertTrue($user->address === null || mb_strlen($user->address) <= 255);
        $this->assertModelExists($user);
    }

    public function test_profile_attributes_can_be_mass_assigned(): void
    {
        $user = User::query()->create([
            'name' => 'Profile User',
            'email' => 'profile@example.com',
            'phone' => '0912345678',
            'address' => '123 Demo Street',
            'password' => 'a-secure-local-password',
        ]);

        $this->assertSame('0912345678', $user->phone);
        $this->assertSame('123 Demo Street', $user->address);
        $this->assertModelExists($user);
    }

    public function test_local_demo_seeder_is_idempotent(): void
    {
        $this->seed(DemoUserSeeder::class);
        $this->seed(DemoUserSeeder::class);

        $user = User::query()->where('email', 'demo@example.com')->sole();

        $this->assertSame(1, User::query()->where('email', 'demo@example.com')->count());
        $this->assertSame('0900000000', $user->phone);
        $this->assertTrue(Hash::check('password', $user->password));
    }
}
