<?php

declare(strict_types=1);

namespace Diapason\Config;

use Diapason\Exception\ConfigException;

final class ConfigLoader
{
    /** @var list<string> Resolved in order; first match wins. */
    private const array CANDIDATES = [
        'diapason.php',
        'diapason.dist.php',
        '.diapason.php',
        '.diapason.dist.php',
    ];

    public function load(?string $explicit, string $cwd): DiapasonConfig
    {
        if ($explicit !== null) {
            if (!is_file($explicit)) {
                throw new ConfigException(sprintf('Configuration file not found: %s', $explicit));
            }

            return $this->require($explicit);
        }

        foreach (self::CANDIDATES as $name) {
            $path = $cwd . DIRECTORY_SEPARATOR . $name;
            if (is_file($path)) {
                return $this->require($path);
            }
        }

        return DiapasonConfig::defaults();
    }

    private function require(string $path): DiapasonConfig
    {
        $config = require $path;

        if (!$config instanceof DiapasonConfig) {
            throw new ConfigException(sprintf(
                'Configuration file %s must return a Diapason\\Config\\DiapasonConfig instance.',
                $path,
            ));
        }

        return $config;
    }
}
