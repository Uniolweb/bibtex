<?php

declare(strict_types=1);
namespace Uniolweb\Bibtex\Configuration;

use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BibtexSettings
{
    /** @var string */
    public const DEFAULT_SORT = 'none';

    public const ALLOWED_SORT = [
        'year',
        'bibtexEntryType',
    ];

    public const ALLOWED_STYLES = [
        'uniol_de',
        'uniol_en',
    ];

    /** @var string */
    public const DEFAULT_STYLE = 'uniol_en';

    public const DEFAULT_FILTER_TYPE = '';

    private int $uid = 0;

    private string $url = '';

    /** @var string URL regardless of file type */
    private string $unifiedUrl = '';

    private string $style = self::DEFAULT_STYLE;

    /**
     * Currently not used!
     *
     * @var string
     * @deprecated
     */
    private string $template = '';

    private string $fileType = 'none';

    private ?File $file = null;

    public static function initializeWithSettings(array $settings, string $style, bool $addOrigEntry = false): BibtexSettings
    {
        return new BibtexSettings(
            $settings['link'] ?? '',
            $settings['sort'] ?? self::DEFAULT_SORT,
            $style,
            $settings['filterType'] ?? '',
            array_filter(explode(',', $settings['filterEntries'] ?? '')),
            $addOrigEntry
        );
    }

    /**
     * @param string $unifiedUrl
     * @param string $sort
     * @param string $style
     * @param string $filterType
     * @param string[] $filterEntries
     * @param bool $addOrigEntry
     * @param LinkService $linkService
     */
    public function __construct(
        string $unifiedUrl,
        private string $sort = self::DEFAULT_SORT,
        string $style = self::DEFAULT_STYLE,
        private readonly string $filterType = '',
        private readonly array $filterEntries = [],
        private readonly bool $addOrigEntry = false,
        ?LinkService $linkService = null
    ) {
        $this->setStyle($style);
        // check if target is a file
        if (str_starts_with($unifiedUrl, 't3://file')) {
            // target is file
            // TYPO3\CMS\Core\LinkHandling\LinkService::resolve()  . This method will return an array with a key  file  containing a  TYPO3\CMS\Core\Resource\FileInterface
            // https://copyprogramming.com/howto/typo3-11-convert-t3-file-uri-into-file-identifier
            if (!$linkService) {
                $linkService = GeneralUtility::makeInstance(LinkService::class);
                $result = $linkService->resolve($unifiedUrl);
                if (
                    ($result['file'] ?? false)
                    && ($result['type'] ?? false)
                    && ($result['type'] === 'file')) {
                    $this->file = $result['file'];
                    $this->fileType = 'file';
                }
            }
        } else {
            // target is url
            $this->url = $unifiedUrl;
            $this->fileType = 'url';
        }
        $this->unifiedUrl = $unifiedUrl;
    }

    /**
     * @return bool
     */
    public function isAddOrigEntry(): bool
    {
        return $this->addOrigEntry;
    }

    /**
     * @return int
     */
    public function getUid(): int
    {
        return $this->uid;
    }

    public function getDefaultSort(): string
    {
        return self::DEFAULT_SORT;
    }

    /**
     * @return string
     */
    public function getSort(): string
    {
        if ($this->sort === 'none') {
            return '';
        }
        if (!in_array($this->sort, self::ALLOWED_SORT)) {
            return '';
        }
        return $this->sort;
    }

    /**
     * @param string $sort
     */
    public function setSort(string $sort): void
    {
        $this->sort = $sort;
    }

    /**
     * @param string $style
     */
    public function setStyle(string $style): void
    {
        if (!in_array($style, self::ALLOWED_STYLES)) {
            $style = self::DEFAULT_STYLE;
        }

        $this->style = $style;
    }

    /**
     * @return string
     */
    public function getStyle(): string
    {
        return $this->style;
    }

    /**
     * @return string
     */
    public function getFilterType(): string
    {
        return $this->filterType;
    }

    /**
     * @return string[]
     */
    public function getAllow(): array
    {
        if ($this->getFilterType() === 'allow') {
            return $this->getFilterEntries();
        }
        return [];
    }

    /**
     * @return string[]
     */
    public function getDeny(): array
    {
        if ($this->getFilterType() === 'deny') {
            return $this->getFilterEntries();
        }
        return [];
    }

    /**
     * @return string[]
     */
    public function getFilterEntries(): array
    {
        return $this->filterEntries;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get URL regardless of file type (URL or file)
     *
     * @return string
     */
    public function getUnifiedUrl(): string
    {
        return $this->unifiedUrl;
    }

    /**
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @return ?File
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getFileUrl(): string
    {
        if ($this->file) {
            return $this->file->getPublicUrl();
        }
        return '';
    }

    /**
     * @return string
     * @deprecated
     */
    public function getTemplate(): string
    {
        return $this->template;
    }
}
