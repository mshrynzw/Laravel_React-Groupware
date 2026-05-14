<?php

namespace App\Support;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><ul><ol><li><a><h2><h3><blockquote><code><pre>';

    public static function sanitize(string $html): string
    {
        $stripped = strip_tags($html, self::ALLOWED_TAGS);

        return preg_replace('/\s(on\w+)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $stripped) ?? $stripped;
    }

    public static function containsDangerousPatterns(string $html): bool
    {
        if (preg_match('/javascript\s*:/i', $html)) {
            return true;
        }

        return (bool) preg_match('/<\s*script/i', $html);
    }
}
