<?php

declare(strict_types=1);

namespace Diapason\Exception;

use RuntimeException;

final class XliffLoadException extends RuntimeException implements DiapasonException
{
    public const string KIND_WELL_FORMED = 'xml.well-formed';

    private function __construct(
        public readonly string $path,
        public readonly string $kind,
        public readonly string $shortMessage,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function syntaxError(string $path, string $detail): self
    {
        $short = sprintf('XML syntax error: %s', $detail);

        return new self(
            path: $path,
            kind: self::KIND_WELL_FORMED,
            shortMessage: $short,
            message: sprintf('XML syntax error in %s: %s', $path, $detail),
        );
    }

    public static function missingRootElement(string $path): self
    {
        $short = 'XML document has no root element';

        return new self(
            path: $path,
            kind: self::KIND_WELL_FORMED,
            shortMessage: $short,
            message: sprintf('XML document %s has no root element', $path),
        );
    }
}
