<?php

namespace Tests\Feature\Incident;

use App\Models\Incident;
use App\Models\IncidentOccurrence;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use LogicException;
use Tests\TestCase;

class IncidentPersistenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_incident_factory_persists_a_valid_incident(): void
    {
        $incident = Incident::factory()->create();

        $this->assertModelExists($incident);
        $this->assertSame(64, strlen($incident->fingerprint));
        $this->assertSame('accumulating', $incident->status);
        $this->assertNotNull($incident->window_started_at);
    }

    public function test_incident_owns_sanitized_occurrences_and_cascades_them_on_delete(): void
    {
        $incident = Incident::factory()->create();
        $occurrence = IncidentOccurrence::factory()->for($incident)->create();

        $this->assertTrue($incident->occurrences->contains($occurrence));
        $this->assertSame($incident->id, $occurrence->incident->id);
        $this->assertSame('unknown', $occurrence->sanitized_context['validation_state']);

        $incident->delete();

        $this->assertModelMissing($occurrence);
    }

    public function test_incident_source_commit_is_immutable_after_creation(): void
    {
        $incident = Incident::factory()->create();

        $this->expectException(LogicException::class);

        $incident->update(['source_commit' => str_repeat('b', 40)]);
    }
}
