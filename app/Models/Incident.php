<?php

namespace App\Models;

use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['fingerprint', 'environment', 'application', 'source_commit', 'route_name', 'method', 'path', 'exception_class', 'normalized_message', 'occurrence_count', 'threshold', 'window_seconds', 'window_started_at', 'first_seen_at', 'last_seen_at', 'triggered_at', 'status', 'resolved_at'])]
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory, HasUlids;

    /** @return HasMany<IncidentOccurrence, $this> */
    public function occurrences(): HasMany
    {
        return $this->hasMany(IncidentOccurrence::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'window_started_at' => 'immutable_datetime', 'first_seen_at' => 'immutable_datetime',
            'last_seen_at' => 'immutable_datetime', 'triggered_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $incident): void {
            if ($incident->isDirty('source_commit')) {
                throw new LogicException('An incident source commit is immutable.');
            }
        });
    }
}
