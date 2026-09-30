<?php

namespace Tests\Unit\Services\Incidents;

use App\Services\Incidents\IncidentFingerprint;
use PHPUnit\Framework\TestCase;

class IncidentFingerprintTest extends TestCase
{
    public function test_dynamic_identifiers_produce_the_same_fingerprint(): void
    {
        $service = new IncidentFingerprint;

        $first = $service->generate('QueryException', null, '/users/123', 'User 123 with 550e8400-e29b-41d4-a716-446655440000 failed', 'app/Profile.php:10');
        $second = $service->generate('QueryException', null, '/users/987', 'User 987 with 73f7b3b4-9e26-4f89-8b3e-81bba53eb123 failed', 'app/Profile.php:10');

        $this->assertSame($first, $second);
        $this->assertSame(64, strlen($first));
    }

    public function test_a_different_exception_or_frame_produces_a_different_fingerprint(): void
    {
        $service = new IncidentFingerprint;

        $first = $service->generate('QueryException', 'profile.update', '/profile', 'phone is null', 'app/Profile.php:10');
        $second = $service->generate('RuntimeException', 'profile.update', '/profile', 'phone is null', 'app/Other.php:20');

        $this->assertNotSame($first, $second);
    }

    public function test_normalized_message_does_not_retain_email_or_identifiers(): void
    {
        $normalized = (new IncidentFingerprint)->normalizeMessage('User demo@example.com id 123 failed');

        $this->assertSame('User {email} id {number} failed', $normalized);
    }
}
