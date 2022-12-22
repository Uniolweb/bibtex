<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Configuration;

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

    /** @var bool if true, do not show select to select sort order */
    public const DEFAULT_SORT_FIXED = true;

    public const DEFAULT_FILTER_TYPE = '';

    private string $url = '';

    /**
     * @var string
     */
    private $sort = self::DEFAULT_SORT;

    /**
     * Do not show form to select sorting,
     * use the sorting that was selected in settings.
     *
     * @var bool
     */
    private $sortFixed = self::DEFAULT_SORT_FIXED;

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

    private int $fileRef = 0;

    /**
     * @param string $fileType
     * @param string $sort
     * @param bool $sortFixed
     * @param string $style
     * @param string $filterType
     * @param string[] $filterEntries
     */
    public function __construct(
        string $fileType,
        string $url,
        string $sort = self::DEFAULT_SORT,
        bool $sortFixed = self::DEFAULT_SORT_FIXED,
        string $style = self::DEFAULT_STYLE,
        string $filterType = '',
        array $filterEntries = []
    ) {
        $this->fileType = $fileType;
        if ($fileType === 'url') {
            $this->url = $url;
        } else {
            $this->fileRef = (int)$url;
        }
        $this->sort = $sort;
        $this->sortFixed = $sortFixed;
        $this->setStyle($style);
        $this->filterType = $filterType;
        $this->filterEntries = $filterEntries;
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
     * @return bool
     */
    public function isSortFixed(): bool
    {
        return $this->sortFixed;
    }

    /**
     * @param bool $sortFixed
     */
    public function setSortFixed(bool $sortFixed): void
    {
        $this->sortFixed = $sortFixed;
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
     * @return int
     */
    public function getFileRef(): int
    {
        return $this->fileRef;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
