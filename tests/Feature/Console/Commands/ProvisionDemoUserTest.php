<?php

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProvisionDemoUserTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_staging_command_creates_and_updates_one_demo_user(): void
    {
        $this->app->detectEnvironment(fn (): string => 'staging');

        $this->artisan('demo:user', [
            '--email' => 'DEMO@example.com',
            '--name' => 'First Demo User',
            '--phone' => '0911111111',
            '--address' => 'First address',
        ])
            ->expectsQuestion('Password', 'first-demo-password')
            ->expectsQuestion('Confirm password', 'first-demo-password')
            ->assertSuccessful();

        $this->artisan('demo:user', [
            '--email' => 'demo@example.com',
            '--name' => 'Updated Demo User',
            '--phone' => '0922222222',
        ])
            ->expectsQuestion('Password', 'updated-demo-password')
            ->expectsQuestion('Confirm password', 'updated-demo-password')
            ->assertSuccessful();

        $user = User::query()->where('email', 'demo@example.com')->sole();

        $this->assertSame(1, User::query()->where('email', 'demo@example.com')->count());
        $this->assertSame('Updated Demo User', $user->name);
        $this->assertSame('0922222222', $user->phone);
        $this->assertNull($user->address);
        $this->assertTrue(Hash::check('updated-demo-password', $user->password));
    }

    public function test_command_rejects_invalid_profile_data_before_asking_for_a_password(): void
    {
        $this->app->detectEnvironment(fn (): string => 'staging');

        $this->artisan('demo:user', [
            '--email' => 'not-an-email',
        ])->assertFailed();

        $this->assertDatabaseEmpty('users');
    }

    public function test_command_is_unavailable_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->artisan('demo:user')->assertFailed();

        $this->assertDatabaseEmpty('users');
    }
}
