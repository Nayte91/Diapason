<?php

declare(strict_types=1);

namespace Diapason\Parser;

use Diapason\Exception\XliffLoadException;
use DOMDocument;
use DOMElement;

final readonly class XliffDomLoader
{
    /**
     * @throws XliffLoadException
     */
    public function load(string $path): DOMDocument
    {
        $doc = new DOMDocument();
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $loaded = $doc->load($path, LIBXML_NOERROR | LIBXML_NOWARNING);
            $libxmlErrors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        if ($loaded === false) {
            $detail = $libxmlErrors !== []
                ? trim($libxmlErrors[0]->message)
                : 'Unable to load XML document';

            throw XliffLoadException::syntaxError($path, $detail);
        }

        if (!$doc->documentElement instanceof DOMElement) {
            throw XliffLoadException::missingRootElement($path);
        }

        return $doc;
    }
}
