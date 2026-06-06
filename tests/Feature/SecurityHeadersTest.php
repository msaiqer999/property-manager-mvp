<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_response_includes_security_headers()
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_csrf_protection_on_post_requests()
    {
        $response = $this->post('/api/test', []);

        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_sanitization_removes_xss_vectors()
    {
        $maliciousInput = '<script>alert("XSS")</script>';
        $sanitized = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        $this->assertNotContains('<script>', $sanitized);
        $this->assertStringContainsString('&lt;script&gt;', $sanitized);
    }
}
