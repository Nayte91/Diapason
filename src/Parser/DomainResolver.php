<?php

declare(strict_types=1);

namespace Diapason\Parser;

use Diapason\Exception\InputException;

final class DomainResolver
{
    /** @return array{0: string, 1: string}|null */
    public function splitFilename(string $filename): ?array
    {
        if (!str_ends_with($filename, '.xlf')) {
            return null;
        }

        $stem = substr($filename, 0, -4);
        $lastDot = strrpos($stem, '.');
        if ($lastDot === false) {
            return null;
        }

        $domain = substr($stem, 0, $lastDot);
        $locale = substr($stem, $lastDot + 1);

        return [$domain, $locale];
    }

    /** @return list<string> */
    public function resolveDomainPrefix(string $prefix, string $cwd): array
    {
        $base = $this->isAbsoluteOrRelativePath($prefix) ? $prefix : $cwd . DIRECTORY_SEPARATOR . $prefix;
        $pattern = $base . '.*.xlf';

        $matches = glob($pattern);
        if ($matches === false || $matches === []) {
            throw new InputException(sprintf("No files matching '%s.*.xlf' found", $prefix));
        }

        $absolute = [];
        foreach ($matches as $match) {
            if (!is_file($match)) {
                continue;
            }
            $real = realpath($match);
            if ($real === false) {
                continue;
            }
            $absolute[] = $real;
        }

        if ($absolute === []) {
            throw new InputException(sprintf("No files matching '%s.*.xlf' found", $prefix));
        }

        sort($absolute);

        return array_values(array_unique($absolute));
    }

    /**
     * @param list<string> $globs
     * @return list<string>
     */
    public function resolveGlobs(array $globs, string $cwd): array
    {
        $collected = [];

        foreach ($globs as $glob) {
            $pattern = $this->isAbsoluteOrRelativePath($glob) ? $glob : $cwd . DIRECTORY_SEPARATOR . $glob;
            $matches = glob($pattern);
            if ($matches === false) {
                continue;
            }

            foreach ($matches as $match) {
                if (!is_file($match)) {
                    continue;
                }
                $real = realpath($match);
                if ($real === false) {
                    continue;
                }
                $collected[$real] = true;
            }
        }

        $paths = array_keys($collected);
        sort($paths);

        return $paths;
    }

    private function isAbsoluteOrRelativePath(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        if ($value[0] === '/' || $value[0] === '\\') {
            return true;
        }
        if (preg_match('/^[A-Za-z]:[\\\\\/]/', $value) === 1) {
            return true;
        }

        return str_contains($value, '/') || str_contains($value, '\\');
    }
}
