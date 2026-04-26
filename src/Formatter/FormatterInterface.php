<?php

declare(strict_types=1);

namespace Diapason\Formatter;

use DOMDocument;

interface FormatterInterface
{
    /**
     * Formats the document in-place. The caller is expected to have loaded
     * the document with LIBXML_NOBLANKS so that pre-existing insignificant
     * whitespace text nodes are already stripped.
     */
    public function format(DOMDocument $doc): void;
}
