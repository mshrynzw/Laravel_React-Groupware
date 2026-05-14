<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_strips_disallowed_tags(): void
    {
        $html = '<p>ok</p><script>x</script>';
        $out = HtmlSanitizer::sanitize($html);
        $this->assertStringContainsString('<p>ok</p>', $out);
        $this->assertStringNotContainsString('<script', strtolower($out));
    }

    public function test_detects_javascript_uri(): void
    {
        $this->assertTrue(HtmlSanitizer::containsDangerousPatterns('<a href="javascript:alert(1)">x</a>'));
    }
}
