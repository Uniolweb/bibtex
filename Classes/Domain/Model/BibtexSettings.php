<?php

namespace Uniolit\Bibtex\Domain\Model;

class BibtexSettings
{
    const DEFAULT_SORT = 'none';

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
    private $sortFixed = false;

    public function getDefaultSort() : string
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



}