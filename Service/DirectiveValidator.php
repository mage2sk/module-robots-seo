<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Service;

/**
 * Central whitelist + sanitiser for every piece of admin-controlled robots
 * data that eventually flows into a response header or robots.txt body.
 *
 * Two attack surfaces are covered:
 *
 *  1. Robots meta / X-Robots-Tag directive strings. If a merchant accidentally
 *     saves `index, follow\r\nSet-Cookie: ...` into an entity override, we
 *     MUST NOT echo that verbatim into the header.
 *
 *  2. User-agent strings in the `panth_seo_robots_policy` table. These get
 *     written into the robots.txt body as `User-agent: <value>` lines. An
 *     attacker with admin access could try to inject CRLF, semicolons, or
 *     control bytes to smuggle additional directives or break the parser.
 *     A strict printable-ASCII whitelist rejects that at save-time.
 */
class DirectiveValidator
{
    /**
     * Valid robots tokens per Google / Bing documentation. Any token that
     * contains `:` is a key:value pair (e.g. `max-snippet:-1`); the key must
     * appear in this list. Case is preserved but comparison is
     * case-insensitive.
     *
     * @var string[]
     */
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

    /**
     * Valid literal values for `max-image-preview`. Anything else is stripped.
     *
     * @var string[]
     */
    public const VALID_IMAGE_PREVIEW = ['none', 'standard', 'large'];

    /**
     * Default fallback directive returned when an admin-supplied value fails
     * the whitelist. Always safe: "index,follow" is the intended behaviour
     * for an unconfigured store.
     */
    public const DEFAULT_DIRECTIVE = 'index,follow';

    /**
     * Check a user-agent string against a strict printable-ASCII whitelist.
     *
     * Allows letters, digits, dots, underscores, hyphens, plus signs, stars,
     * forward slashes, and spaces. Rejects CR, LF, semicolons, control bytes,
     * and any non-ASCII byte. This is tight enough to defeat CRLF injection
     * into the robots.txt body and permissive enough to cover every real bot
     * UA string published by crawler operators.
     */
    public function isValidUserAgent(string $userAgent): bool
    {
        $userAgent = trim($userAgent);
        if ($userAgent === '' || strlen($userAgent) > 128) {
            return false;
        }
        return (bool) preg_match('/^[A-Za-z0-9._\-+*\/ ]+$/', $userAgent);
    }

    /**
     * Validate that a robots directive string consists only of known tokens
     * and safe characters. Returns true when the value is safe to echo into
     * either `<meta name="robots">` or `X-Robots-Tag`.
     */
    public function isValidDirective(string $directive): bool
    {
        $directive = trim($directive);
        if ($directive === '' || strlen($directive) > 256) {
            return false;
        }
        // Reject any control char. This MUST run before the token check so
        // a value like "noindex\r\nX-Cache: MISS" never even reaches the
        // splitter.
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
                // Integer (possibly -1). Anything else is invalid.
                if (!preg_match('/^-?\d+$/', $value)) {
                    return false;
                }
            }
            if ($key === 'unavailable_after') {
                // Free-form RFC850 date per Google docs, but must still be
                // printable ASCII sans any control bytes. The outer check
                // already handles that.
                if ($value === '') {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Return a safe version of the directive. If validation fails the default
     * directive is returned instead — the caller can always trust the result
     * is header/meta-safe.
     */
    public function sanitizeDirective(string $directive): string
    {
        $directive = trim($directive);
        if ($this->isValidDirective($directive)) {
            return $directive;
        }
        return self::DEFAULT_DIRECTIVE;
    }

    /**
     * Validate an allow/disallow value.
     */
    public function isValidAction(string $action): bool
    {
        $action = strtolower(trim($action));
        return in_array($action, ['allow', 'disallow'], true);
    }

    /**
     * Validate a path string. Must start with `/` and contain only safe
     * characters that are legal inside a robots.txt `Disallow:` / `Allow:`
     * line. CR/LF are rejected so saved rows can never inject new directives
     * into the generated robots.txt body.
     */
    public function isValidPath(string $path): bool
    {
        $path = trim($path);
        if ($path === '' || strlen($path) > 1024) {
            return false;
        }
        if ($path[0] !== '/') {
            return false;
        }
        // Reject control characters.
        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return false;
        }
        return true;
    }
}
