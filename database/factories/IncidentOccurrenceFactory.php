<?php

namespace Database\Factories;

use App\Models\Incident;
use App\Models\IncidentOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncidentOccurrence>
 */
class IncidentOccurrenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'incident_id' => Incident::factory(),
            'request_id' => fake()->uuid(),
            'stack_trace' => "app/Http/Controllers/ProfileController.php:42\n",
            'sanitized_context' => [
                'present_fields' => ['name', 'email', 'phone', 'address'],
                'validation_state' => 'unknown',
            ],
            'occurred_at' => now(),
        ];
    }
}
