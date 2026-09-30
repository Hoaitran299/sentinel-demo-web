<?php

namespace App\Services\Incidents;

use Illuminate\Support\Str;

final class IncidentFingerprint
{
    public function generate(string $exceptionClass, ?string $routeName, string $path, string $message, ?string $topApplicationFrame): string
    {
        $routeIdentity = filled($routeName) ? $routeName : $this->normalizePath($path);

        return hash('sha256', implode("\n", [
            $exceptionClass,
            $routeIdentity,
            $this->normalizeMessage($message),
            $topApplicationFrame ?? 'unknown-frame',
        ]));
    }

    public function normalizeMessage(string $message): string
    {
        $normalized = preg_replace(
            ['/[A-Z0-9]{26}/i', '/\b[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\b/i', '/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i', '/\b\d+\b/'],
            ['{ulid}', '{uuid}', '{email}', '{number}'],
            $message,
        ) ?? $message;

        return Str::limit((string) Str::of($normalized)->squish(), 1000, '');
    }

    private function normalizePath(string $path): string
    {
        return preg_replace(
            ['#/\d+(?=/|$)#', '#/[0-9a-f-]{36}(?=/|$)#i', '#/[A-Z0-9]{26}(?=/|$)#i'],
            '/{id}',
            '/'.ltrim($path, '/'),
        ) ?? $path;
    }
}
