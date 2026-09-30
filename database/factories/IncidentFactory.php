<?php

namespace Database\Factories;

use App\Models\Incident;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fingerprint' => hash('sha256', fake()->unique()->sentence()),
            'environment' => 'testing',
            'application' => 'demo-agent-ai-auto-fix-bug',
            'source_commit' => str_repeat('a', 40),
            'route_name' => 'profile.update',
            'method' => 'PATCH',
            'path' => '/profile',
            'exception_class' => 'Illuminate\\Database\\QueryException',
            'normalized_message' => 'phone may not be null',
            'occurrence_count' => 1,
            'threshold' => 3,
            'window_seconds' => 600,
            'window_started_at' => now(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'status' => 'accumulating',
        ];
    }
}
