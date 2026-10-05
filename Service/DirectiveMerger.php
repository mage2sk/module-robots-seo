<?php
declare(strict_types=1);

namespace Panth\RobotsSeo\Service;

class DirectiveMerger
{
    private const FLAGS = ['noarchive', 'nosnippet', 'noimageindex', 'notranslate'];

    private const IMAGE_PREVIEW_RANK = ['none' => 0, 'standard' => 1, 'large' => 2];

    private const LIMIT_KEYS = ['max-snippet', 'max-video-preview'];

    public function __construct(
        private readonly DirectiveValidator $validator
    ) {
    }

    public function merge(string ...$values): string
    {
        $index = null;
        $follow = null;
        $flags = [];
        $keyed = [];

        foreach ($values as $value) {
            $value = trim($value);
            if ($value === '' || !$this->validator->isValidDirective($value)) {
                continue;
            }
            foreach (preg_split('/\s*,\s*/', $value) ?: [] as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $key = strtolower($part);
                $arg = '';
                if (str_contains($part, ':')) {
                    [$key, $arg] = explode(':', $part, 2);
                    $key = strtolower(trim($key));
                    $arg = trim($arg);
                }

                if ($key === 'none') {
                    $index = 'noindex';
                    $follow = 'nofollow';
                } elseif ($key === 'all') {
                    $index ??= 'index';
                    $follow ??= 'follow';
                } elseif ($key === 'noindex') {
                    $index = 'noindex';
                } elseif ($key === 'index') {
                    $index ??= 'index';
                } elseif ($key === 'nofollow') {
                    $follow = 'nofollow';
                } elseif ($key === 'follow') {
                    $follow ??= 'follow';
                } elseif (in_array($key, self::FLAGS, true)) {
                    $flags[$key] = $key;
                } elseif (in_array($key, self::LIMIT_KEYS, true)) {
                    $keyed[$key] = isset($keyed[$key]) ? $this->stricterLimit($keyed[$key], $arg) : $arg;
                } elseif ($key === 'max-image-preview') {
                    $keyed[$key] = isset($keyed[$key]) ? $this->stricterPreview($keyed[$key], $arg) : $arg;
                } elseif ($key === 'unavailable_after') {
                    $keyed[$key] ??= $arg;
                }
            }
        }

        $tokens = [];
        if ($index !== null) {
            $tokens[] = $index;
        }
        if ($follow !== null) {
            $tokens[] = $follow;
        }
        foreach ($flags as $flag) {
            $tokens[] = $flag;
        }
        foreach ($keyed as $key => $arg) {
            $tokens[] = $key . ':' . $arg;
        }
        if ($tokens === []) {
            return '';
        }

        return $this->validator->sanitizeDirective(implode(',', $tokens));
    }

    private function stricterLimit(string $current, string $candidate): string
    {
        $a = (int) $current;
        $b = (int) $candidate;
        if ($a < 0) {
            return (string) $b;
        }
        if ($b < 0) {
            return (string) $a;
        }
        return (string) min($a, $b);
    }

    private function stricterPreview(string $current, string $candidate): string
    {
        $a = self::IMAGE_PREVIEW_RANK[$current] ?? 2;
        $b = self::IMAGE_PREVIEW_RANK[$candidate] ?? 2;
        return $b < $a ? $candidate : $current;
    }
}
