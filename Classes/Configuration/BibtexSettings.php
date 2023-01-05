<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Configuration;

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolit\Bibtex\Service\FileService;

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

    private bool $addOrigEntry = false;

    private int $uid = 0;

    private string $url = '';

    /**
     * @var string
     */
    private $sort = self::DEFAULT_SORT;

    private string $style = self::DEFAULT_STYLE;

    private string $filterType = '';

    /** @var string[] */
    private array $filterEntries;

    /**
     * Currently not used!
     *
     * @var string
     */
    private string $template = '';

    private string $fileType = 'url';

    private ?FileReference $fileReference = null;

    public static function initializeWithSettings(array $settings, int $uid, string $style, bool $addOrigEntry = false): BibtexSettings
    {
        return new BibtexSettings(
            $uid,
            $settings['fileType'] ?? 'url',
            $settings['link'] ?? '',
            (int)($settings['file'] ?? 0),
            $settings['sort'] ?? self::DEFAULT_SORT,
            $style,
            $settings['filterType'] ?? '',
            array_filter(explode(',', $settings['filterEntries'] ?? '')),
            $addOrigEntry
        );
    }

    /**
     * @param int $uid
     * @param string $fileType
     * @param string $url
     * @param int $numFiles
     * @param string $sort
     * @param string $style
     * @param string $filterType
     * @param string[] $filterEntries
     * @param bool $addOrigEntry
     * @param ?FileService $fileService
     */
    public function __construct(
        int $uid,
        string $fileType,
        string $url,
        int $numFiles,
        string $sort = self::DEFAULT_SORT,
        string $style = self::DEFAULT_STYLE,
        string $filterType = '',
        array $filterEntries = [],
        bool $addOrigEntry = false,
        ?FileService $fileService = null
    ) {
        $uid = $uid;
        $this->sort = $sort;
        $this->addOrigEntry = $addOrigEntry;
        $this->setStyle($style);
        $this->filterType = $filterType;
        $this->filterEntries = $filterEntries;

        $this->fileType = $fileType;
        if ($fileType === 'url') {
            $this->url = $url;
        } elseif ($numFiles > 0) {
            if (!$fileService) {
                $fileService = GeneralUtility::makeInstance(FileService::class);
            }
            $this->initializeFileRef($uid, $fileService);
        }
    }

    protected function initializeFileRef(int $uid, FileService $fileService): void
    {
        $fileReferenceObjects = $fileService->getFileObjectsByRelations('tt_content', 'pi_flexform', $uid);
        $fileReferenceObject = reset($fileReferenceObjects);
        $this->fileReference = $fileReferenceObject;
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
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @return ?FileReference
     */
    public function getFileRef(): ?FileReference
    {
        return $this->fileReference;
    }

    /**
     * @return string
     */
    public function getFileUrl(): string
    {
        if ($this->fileReference) {
            return $this->fileReference->getPublicUrl();
        }
        return '';
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
