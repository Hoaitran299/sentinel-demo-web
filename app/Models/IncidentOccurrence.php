<?php

namespace App\Models;

use Database\Factories\IncidentOccurrenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['incident_id', 'request_id', 'stack_trace', 'sanitized_context', 'occurred_at'])]
class IncidentOccurrence extends Model
{
    /** @use HasFactory<IncidentOccurrenceFactory> */
    use HasFactory, HasUlids;

    /** @return BelongsTo<Incident, $this> */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['sanitized_context' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
