<?php

namespace App\Services\Incidents;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class IncidentContextSanitizer
{
    private const int MAX_BYTES = 8192;

    /** @var list<string> */
    private const array FORBIDDEN_KEYS = ['password', 'authorization', 'cookie', 'session', 'api_key', 'token', 'secret', 'file', 'contents'];

    /** @var list<string> */
    private const array MASKED_KEYS = ['email', 'phone', 'address', 'ip'];

    /** @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function sanitize(array $context): array
    {
        $sanitized = [
            'present_fields' => collect(Arr::get($context, 'present_fields', []))
                ->filter(fn (mixed $field): bool => is_string($field))
                ->map(fn (string $field): string => Str::limit($field, 64, ''))
                ->unique()->take(50)->values()->all(),
            'validation_state' => Str::limit((string) Arr::get($context, 'validation_state', 'unknown'), 64, ''),
            'route_parameters' => $this->sanitizeArray((array) Arr::get($context, 'route_parameters', [])),
        ];

        while (strlen((string) json_encode($sanitized)) > self::MAX_BYTES) {
            $sanitized['_truncated'] = true;
            if ($sanitized['route_parameters'] !== []) {
                array_pop($sanitized['route_parameters']);
            } else {
                array_pop($sanitized['present_fields']);
            }
        }

        return $sanitized;
    }

    /** @param array<array-key, mixed> $values
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $values, int $depth = 0): array
    {
        if ($depth >= 3) {
            return [];
        }

        $result = [];
        foreach (array_slice($values, 0, 20, true) as $key => $value) {
            $normalizedKey = Str::lower((string) $key);
            if (Str::contains($normalizedKey, self::FORBIDDEN_KEYS)) {
                continue;
            }
            if (Str::contains($normalizedKey, self::MASKED_KEYS)) {
                $result[(string) $key] = '[MASKED]';
            } elseif (is_array($value)) {
                $result[(string) $key] = $this->sanitizeArray($value, $depth + 1);
            } elseif (is_scalar($value) || $value === null) {
                $result[(string) $key] = $this->sanitizeScalar($value);
            }
        }

        return $result;
    }

    private function sanitizeScalar(string|int|float|bool|null $value): string|int|float|bool|null
    {
        if (! is_string($value)) {
            return $value;
        }
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return '[MASKED]';
        }
        if (preg_match('/(?:bearer\s+|gh[pousr]_|github_pat_)/i', $value) === 1) {
            return '[REDACTED]';
        }

        return Str::limit($value, 200, '');
    }
}
