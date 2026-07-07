<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Service;

class DirectiveValidator
{
    public const VALID_TOKENS = [
        'index',
        'noindex',
        'follow',
        'nofollow',
        'noarchive',
        'nosnippet',
        'noimageindex',
        'notranslate',
        'max-snippet',
        'max-image-preview',
        'max-video-preview',
        'unavailable_after',
        'none',
        'all',
    ];

    public const VALID_IMAGE_PREVIEW = ['none', 'standard', 'large'];

    public const DEFAULT_DIRECTIVE = 'index,follow';

    public function isValidUserAgent(string $userAgent): bool
    {
        $userAgent = trim($userAgent);
        if ($userAgent === '' || strlen($userAgent) > 128) {
            return false;
        }
        return (bool) preg_match('/^[A-Za-z0-9._\-+*\/ ]+$/', $userAgent);
    }

    public function isValidDirective(string $directive): bool
    {
        $directive = trim($directive);
        if ($directive === '' || strlen($directive) > 256) {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $directive)) {
            return false;
        }
        $parts = preg_split('/\s*,\s*/', $directive) ?: [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $key = $part;
            $value = '';
            if (str_contains($part, ':')) {
                [$key, $value] = explode(':', $part, 2);
                $key = strtolower(trim($key));
                $value = trim($value);
            } else {
                $key = strtolower($key);
            }
            if (!in_array($key, array_map('strtolower', self::VALID_TOKENS), true)) {
                return false;
            }
            if ($key === 'max-image-preview' && !in_array($value, self::VALID_IMAGE_PREVIEW, true)) {
                return false;
            }
            if (in_array($key, ['max-snippet', 'max-video-preview'], true)) {
                if (!preg_match('/^-?\d+$/', $value)) {
                    return false;
                }
            }
            if ($key === 'unavailable_after') {
                if ($value === '') {
                    return false;
                }
            }
        }
        return true;
    }

    public function sanitizeDirective(string $directive): string
    {
        $directive = trim($directive);
        if ($this->isValidDirective($directive)) {
            return $directive;
        }
        return self::DEFAULT_DIRECTIVE;
    }

    public function isValidAction(string $action): bool
    {
        $action = strtolower(trim($action));
        return in_array($action, ['allow', 'disallow'], true);
    }

    public function isValidPath(string $path): bool
    {
        $path = trim($path);
        if ($path === '' || strlen($path) > 1024) {
            return false;
        }
        if ($path[0] !== '/') {
            return false;
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return false;
        }
        return true;
    }
}
