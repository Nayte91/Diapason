<?php

declare(strict_types=1);

use Diapason\Check\IssueIdFilterCheck;
use Diapason\Config\DiapasonConfig;
use Diapason\Config\FormatMode;

return DiapasonConfig::configure()
    ->withPaths('custom/*.xlf')
    ->withChecks(
        new IssueIdFilterCheck('xml.well-formed', 'XLIFF file must be well-formed XML.', 'xml.well-formed'),
        new IssueIdFilterCheck('xliff.srcLang', 'XLIFF root element must declare srcLang attribute.', 'xliff.srcLang'),
    )
    ->withFormatMode(FormatMode::Disabled);
