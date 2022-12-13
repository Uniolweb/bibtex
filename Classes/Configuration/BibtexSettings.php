<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Configuration;

class BibtexSettings
{
    /** @var string */
    public const DEFAULT_SORT = 'none';

    /** @var string */
    public const DEFAULT_STYLE = 'uniol';

    /** @var bool if true, do not show select to select sort order */
    public const DEFAULT_SORT_FIXED = true;

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

    /** @var string[] */
    private array $allow;

    /** @var string[] */
    private array $deny;

    /**
     * Currently not used!
     *
     * @var string
     */
    private string $template = '';

    /**
     * @param string $sort
     * @param bool $sortFixed
     * @param string $style
     * @param string[] $allow
     * @param string[] $deny
     */
    public function __construct(
        string $url,
        string $sort = self::DEFAULT_SORT,
        bool $sortFixed = self::DEFAULT_SORT_FIXED,
        string $style = self::DEFAULT_STYLE,
        array $allow = [],
        array $deny = []
    ) {
        $this->url = $url;
        $this->sort = $sort;
        $this->sortFixed = $sortFixed;
        $this->style = $style;
        $this->allow = $allow;
        $this->deny = $deny;
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
        if ($this->allow) {
            return 'allow';
        }
        if ($this->deny) {
            return 'deny';
        }
        return '';
    }

    /**
     * @return string[]
     */
    public function getAllow(): array
    {
        return $this->allow;
    }

    /**
     * @return string[]
     */
    public function getDeny(): array
    {
        return $this->deny;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }
}
