<?php

declare(strict_types=1);
namespace Uniolweb\Bibtex\Cache;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolweb\Bibtex\Bibtex2Html\Service\FetchContentResult;

class BibtexCache
{
    private readonly FrontendInterface $cache;

    /**
     * @todo Configure in Services.yaml
     */
    public function __construct()
    {
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('bibtex_bibtexcache');
    }

    /**
     * Caches metadata
     *
     * @todo make lifetime configurable
     */
    public function set(string $url, FetchContentResult $result, bool $overwrite = true): void
    {
        $hash = $this->getIdentifier($url);
        if ($overwrite && $this->cache->get($hash)) {
            $this->cache->remove($hash);
        }
        $this->cache->set($hash, $result, ['bibtex'], 86400);
    }

    public function get(string $url): ?FetchContentResult
    {
        $result = $this->cache->get($this->getIdentifier($url));
        if (!$result || ! $result instanceof  FetchContentResult) {
            return null;
        }
        return $result;
    }

    protected function getIdentifier(string $url): string
    {
        return md5($url);
    }
}
