<?php

declare(strict_types=1);

namespace Diapason\Parser;

use Diapason\Exception\XliffLoadException;
use Diapason\Model\Group;
use Diapason\Model\Issue;
use Diapason\Model\IssueList;
use Diapason\Model\Segment;
use Diapason\Model\Severity;
use Diapason\Model\Unit;
use Diapason\Model\XliffFile;
use DOMDocument;
use DOMElement;

final readonly class XliffParser
{
    private const string XLIFF_NS = 'urn:oasis:names:tc:xliff:document:2.0';

    public function __construct(
        private DomainResolver $domainResolver,
        private XliffDomLoader $domLoader,
    ) {}

    public function parseFile(string $path): XliffFile
    {
        try {
            $doc = $this->domLoader->load($path);
        } catch (XliffLoadException $e) {
            return $this->makeInvalidFromLoadException($path, $e);
        }

        return $this->parse($doc, $path);
    }

    public function parse(DOMDocument $doc, string $path): XliffFile
    {
        $filename = basename($path);
        $split = $this->domainResolver->splitFilename($filename);
        $domain = $split[0] ?? '';
        $locale = $split[1] ?? '';

        $root = $doc->documentElement;
        if (!$root instanceof DOMElement) {
            return $this->makeInvalid(
                path: $path,
                filename: $filename,
                domain: $domain,
                locale: $locale,
                checkId: 'xml.well-formed',
                message: 'XML document has no root element',
            );
        }

        if ($root->namespaceURI !== self::XLIFF_NS || $root->localName !== 'xliff') {
            return $this->makeInvalid(
                path: $path,
                filename: $filename,
                domain: $domain,
                locale: $locale,
                checkId: 'xliff.namespace',
                message: sprintf(
                    'Invalid root element: {%s}%s',
                    $root->namespaceURI ?? '',
                    $root->localName ?? '',
                ),
            );
        }

        $issues = new IssueList();
        $valid = true;

        $version = $root->getAttribute('version');
        if (!in_array($version, ['2.0', '2.1'], true)) {
            $valid = false;
            $issues->add(new Issue(
                severity: Severity::Error,
                checkId: 'xliff.namespace',
                file: $path,
                message: sprintf('Invalid XLIFF version: %s', $version === '' ? '(none)' : $version),
            ));
        }

        $srcLang = $root->getAttribute('srcLang');
        if ($srcLang === '') {
            $valid = false;
            $issues->add(new Issue(
                severity: Severity::Error,
                checkId: 'xliff.srcLang',
                file: $path,
                message: 'Missing srcLang attribute',
            ));
        }

        $isSource = $locale !== '' && $locale === $srcLang;

        $extracted = $this->extractGroupsAndUnits($root, $path);
        $issues->addAll($extracted['issues']);
        if ($extracted['issues'] !== []) {
            $valid = false;
        }

        return new XliffFile(
            path: $path,
            filename: $filename,
            domain: $domain,
            locale: $locale,
            srcLang: $srcLang,
            isSource: $isSource,
            groups: $extracted['groups'],
            units: $extracted['units'],
            issues: $issues,
            valid: $valid,
        );
    }

    /**
     * @return array{groups: list<Group>, units: array<string, Unit>, issues: list<Issue>}
     */
    private function extractGroupsAndUnits(DOMElement $root, string $path): array
    {
        $groups = [];
        $units = [];
        $issues = [];
        $seenGroups = [];
        $seenUnits = [];

        $groupNodes = $root->getElementsByTagNameNS(self::XLIFF_NS, 'group');

        foreach ($groupNodes as $groupElem) {
            if (!$groupElem instanceof DOMElement) {
                continue;
            }

            $gid = $groupElem->getAttribute('id');
            if ($gid === '') {
                continue;
            }

            if (isset($seenGroups[$gid])) {
                $issues[] = new Issue(
                    severity: Severity::Error,
                    checkId: 'xliff.duplicateGroupId',
                    file: $path,
                    message: sprintf('Duplicate group ID: %s', $gid),
                    groupId: $gid,
                );
                continue;
            }
            $seenGroups[$gid] = true;

            $extractedUnits = $this->extractUnits($groupElem, $gid, $path, $seenUnits);
            $issues = array_merge($issues, $extractedUnits['issues']);
            $seenUnits = $extractedUnits['seenUnits'];

            foreach ($extractedUnits['units'] as $uid => $unit) {
                $units[$uid] = $unit;
            }

            $groups[] = new Group(id: $gid, unitIds: $extractedUnits['unitIds']);
        }

        return ['groups' => $groups, 'units' => $units, 'issues' => $issues];
    }

    /**
     * @param  array<string, true> $seenUnits
     * @return array{units: array<string, Unit>, unitIds: list<string>, issues: list<Issue>, seenUnits: array<string, true>}
     */
    private function extractUnits(DOMElement $groupElem, string $gid, string $path, array $seenUnits): array
    {
        $units = [];
        $unitIds = [];
        $issues = [];

        $unitNodes = $groupElem->getElementsByTagNameNS(self::XLIFF_NS, 'unit');

        foreach ($unitNodes as $unitElem) {
            if (!$unitElem instanceof DOMElement) {
                continue;
            }

            $uid = $unitElem->getAttribute('id');
            if ($uid === '') {
                continue;
            }

            if (isset($seenUnits[$uid])) {
                $issues[] = new Issue(
                    severity: Severity::Error,
                    checkId: 'xliff.duplicateUnitId',
                    file: $path,
                    message: sprintf('Duplicate unit ID: %s', $uid),
                    unitId: $uid,
                    groupId: $gid,
                );
                continue;
            }
            $seenUnits[$uid] = true;
            $unitIds[] = $uid;

            $segments = $this->extractSegments($unitElem);
            $sourceText = $segments === [] ? '' : $segments[0]->source;

            $units[$uid] = new Unit(
                id: $uid,
                source: $sourceText,
                groupId: $gid,
                segments: $segments,
            );
        }

        return ['units' => $units, 'unitIds' => $unitIds, 'issues' => $issues, 'seenUnits' => $seenUnits];
    }

    /** @return list<Segment> */
    private function extractSegments(DOMElement $unitElem): array
    {
        $segments = [];
        $segmentNodes = $unitElem->getElementsByTagNameNS(self::XLIFF_NS, 'segment');

        foreach ($segmentNodes as $segElem) {
            if (!$segElem instanceof DOMElement) {
                continue;
            }

            $state = $segElem->hasAttribute('state') ? $segElem->getAttribute('state') : null;
            $source = $this->firstChildText($segElem, 'source');
            $target = $this->firstChildText($segElem, 'target');

            $segments[] = new Segment(
                state: $state,
                source: $source ?? '',
                target: $target,
            );
        }

        return $segments;
    }

    private function firstChildText(DOMElement $parent, string $localName): ?string
    {
        foreach ($parent->childNodes as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }
            if ($child->namespaceURI === self::XLIFF_NS && $child->localName === $localName) {
                return $child->textContent;
            }
        }

        return null;
    }

    private function makeInvalidFromLoadException(string $path, XliffLoadException $e): XliffFile
    {
        $filename = basename($path);
        $split = $this->domainResolver->splitFilename($filename);

        return $this->makeInvalid(
            path: $path,
            filename: $filename,
            domain: $split[0] ?? '',
            locale: $split[1] ?? '',
            checkId: $e->kind,
            message: $e->shortMessage,
        );
    }

    private function makeInvalid(
        string $path,
        string $filename,
        string $domain,
        string $locale,
        string $checkId,
        string $message,
    ): XliffFile {
        $issues = new IssueList();
        $issues->add(new Issue(
            severity: Severity::Error,
            checkId: $checkId,
            file: $path,
            message: $message,
        ));

        return new XliffFile(
            path: $path,
            filename: $filename,
            domain: $domain,
            locale: $locale,
            srcLang: '',
            isSource: false,
            groups: [],
            units: [],
            issues: $issues,
            valid: false,
        );
    }
}
