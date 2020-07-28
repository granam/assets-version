<?php declare(strict_types=1);

namespace Granam\AssetsVersion;

use Granam\Strict\Object\StrictObject;

class AssetsVersionInjector extends StrictObject
{
    public const VERSION = 'version';
    public const PROBLEM_REPORT_AS_IGNORE = 'ignore';
    public const PROBLEM_REPORT_AS_NOTICE = 'notice';
    public const PROBLEM_REPORT_AS_WARNING = 'warning';
    public const PROBLEM_REPORT_AS_EXCEPTION = 'exception';

    /** @var string */
    private $notFoundAssetFileReport;

    public function __construct(string $problemReport = self::PROBLEM_REPORT_AS_EXCEPTION)
    {
        $this->notFoundAssetFileReport = $problemReport;
    }

    public function addVersionsToAssetLinks(string $content, string $assetsRootDir): string
    {
        $srcFound = \preg_match_all('~(?<sources>(?:src="[^"]+"|src=\'[^\']+\'))~', $content, $sourceMatches);
        $urlFound = \preg_match_all('~(?<urls>(?:url\((?:(?<!data:)[^)])+\)|url\("(?:(?<!data:)[^)])+"\)|url\(\'(?:(?!data:)[^)])+\'\)))~', $content, $urlMatches);
        if (!$srcFound && !$urlFound) {
            return $content; // nothing to change
        }
        $stringsWithLinks = \array_merge($sourceMatches['sources'] ?? [], $urlMatches['urls'] ?? []);
        $replacedContent = $content;
        foreach ($stringsWithLinks as $stringWithLink) {
            $maybeQuotedLink = \preg_replace(['~url\(([^)]+)\)~', '~src="([^"]+)"~', "~src='([^']+)'~"], '$1', $stringWithLink);
            $link = \trim($maybeQuotedLink, '"\'');
            $md5 = $this->getFileMd5($link, $assetsRootDir);
            if (!$md5) {
                continue;
            }
            $versionedLink = $this->appendVersionHashToLink($link, $md5);
            if ($versionedLink === $link) {
                continue; // nothing changed for current link
            }
            $stringWithVersionedLink = \str_replace($link, $versionedLink, $stringWithLink);
            // do NOT replace link directly in content to avoid misleading replacement on places without wrapping url or src
            $replacedContent = \str_replace($stringWithLink, $stringWithVersionedLink, $replacedContent);
        }

        return $replacedContent;
    }

    private function getFileMd5(string $link, string $assetsRootDir): ?string
    {
        $parts = \parse_url($link);
        $localPath = $parts['path'] ?? '';
        if ($localPath === '') {
            $this->reportProblem("Can not parse URL from link '{$link}", $this->notFoundAssetFileReport);
            return null;
        }

        $file = $assetsRootDir . '/' . \ltrim($localPath, '/');
        $md5Sum = \md5_file($file);
        if ($md5Sum === false) {
            $this->reportProblem("Can not read asset file {$file} figured from link '{$parts['path']}", $this->notFoundAssetFileReport);
            return null;
        }

        return $md5Sum;
    }

    private function reportProblem(string $problem, string $reportType): void
    {
        switch ($reportType) {
            case self::PROBLEM_REPORT_AS_IGNORE :
                return;
            case self::PROBLEM_REPORT_AS_NOTICE :
                \trigger_error($problem, E_USER_NOTICE);
                return;
            case self::PROBLEM_REPORT_AS_WARNING :
                \trigger_error($problem, E_USER_WARNING);
                return;
            case self::PROBLEM_REPORT_AS_EXCEPTION :
            default :
                throw new Exceptions\AssetsVersionParsingException($problem, E_USER_WARNING);
        }
    }

    private function appendVersionHashToLink(string $link, string $version): string
    {
        $parsed = \parse_url($link);
        $queryString = \urldecode($parsed['query'] ?? '');
        $queryChunks = explode('&', $queryString);
        $queryParts = [];
        foreach ($queryChunks as $queryChunk) {
            if ($queryChunk === '') {
                continue;
            }
            [$name, $value] = \explode('=', $queryChunk);
            $queryParts[$name] = $value;
        }
        if (!empty($queryParts[self::VERSION]) && $queryParts[self::VERSION] === $version) {
            return $link; // nothing to change
        }
        $queryParts[self::VERSION] = $version;
        $newQueryChunks = [];
        foreach ($queryParts as $name => $value) {
            $newQueryChunks[] = \urlencode($name) . '=' . \urlencode($value);
        }
        $versionedQuery = \implode('&', $newQueryChunks);
        $fragment = '';
        if (($parsed['fragment'] ?? '') !== '') {
            $fragment .= '#' . $parsed['fragment'];
        }
        if ($fragment !== '') {
            $versionedQuery .= '#' . $fragment;
        }
        $withoutQuery = $link;
        $queryStartsAt = \strpos($link, '?');
        if ($queryStartsAt !== false) {
            $withoutQuery = \substr($link, 0, $queryStartsAt);
        }

        return $withoutQuery . '?' . $versionedQuery;
    }
}
