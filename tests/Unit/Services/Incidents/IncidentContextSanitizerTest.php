<?php

namespace Tests\Unit\Services\Incidents;

use App\Services\Incidents\IncidentContextSanitizer;
use PHPUnit\Framework\TestCase;

class IncidentContextSanitizerTest extends TestCase
{
    public function test_sensitive_values_are_removed_or_masked(): void
    {
        $context = (new IncidentContextSanitizer)->sanitize([
            'password' => 'never-store-me',
            'authorization' => 'Bearer never-store-me',
            'present_fields' => ['name', 'email', 'password'],
            'validation_state' => 'unknown',
            'route_parameters' => [
                'user_id' => 42,
                'email' => 'person@example.com',
                'token' => 'github_pat_never_store_me_123456789',
                'safe' => 'Bearer secret-value',
            ],
        ]);

        $encoded = json_encode($context, JSON_THROW_ON_ERROR);

        $this->assertSame('[MASKED]', $context['route_parameters']['email']);
        $this->assertSame('[REDACTED]', $context['route_parameters']['safe']);
        $this->assertArrayNotHasKey('token', $context['route_parameters']);
        $this->assertStringNotContainsString('never-store-me', $encoded);
        $this->assertStringNotContainsString('secret-value', $encoded);
    }

    public function test_context_is_bounded_by_depth_count_and_encoded_size(): void
    {
        $context = (new IncidentContextSanitizer)->sanitize([
            'present_fields' => array_fill(0, 100, str_repeat('field', 30)),
            'route_parameters' => array_fill(0, 30, str_repeat('x', 500)),
        ]);

        $this->assertLessThanOrEqual(8192, strlen(json_encode($context, JSON_THROW_ON_ERROR)));
        $this->assertLessThanOrEqual(50, count($context['present_fields']));
        $this->assertLessThanOrEqual(20, count($context['route_parameters']));
    }
}
