<?php

namespace Uniolit\Bibtex\Controller;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Uniolit\Bibtex\Domain\Model\BibtexSettings;


class BtexController extends ActionController
{

    /**
     * @var Logger
     */
    private $logger;

    /** @var int */
    private $languageId = 0;

    /**
     * @var string
     */
    private $key = '';

    /**
     * @var array
     */
    private $allow = [];

    /**
     * @var array
     */
    private $deny = [];

    /**
     * @var string
     */
    private $bibtexUrl = '';

    /**
     * @var array
     */
    private $urlQueryParams = [];

    private $cache;

    public function initializeAction()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('bibtex_bibtexcache');
    }

    /**
     * @param BibtexSettings $bibtexSettings
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function showAction(BibtexSettings $bibtexSettings = null)
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $this->languageId = (int) ($context->getPropertyFromAspect('language', 'id'));

        if ($bibtexSettings) {
            $sort = $bibtexSettings->getSort();
        } else {
            $sort = $this->settings['sort'] ?? BibtexSettings::DEFAULT_SORT;
            $bibtexSettings = new BibtexSettings();
            $bibtexSettings->setSort($sort);
        }
        $this->key = $this->settings['key'] ?? '';

        if ($this->settings['allow']) {
            $this->allow = explode(',', $this->settings['allow']);
        } else {
            $this->allow = [];
        }
        if ($this->settings['deny']) {
            $this->deny = explode(',', $this->settings['deny']);
        } else {
            $this->deny = [];
        }
        /*
        if ($this->settings['template']) {
            $aOlBibtexPars[] = 'template=' . $this->settings['template'];
        }
        */

        if ($this->settings['sortfixed'] === "1") {
            $bibtexSettings->setSortFixed(true);
        }
        // @todo: remove this once changing the sorting is supported
        $bibtexSettings->setSortFixed(true);

        $this->bibtexUrl = $this->settings['link'];

        $bibtexContent = $this->getBibtexFileContent($this->bibtexUrl);
        if (!$bibtexContent) {
            $convertedContent = 'Bibtex file does not exist.';
            $this->logger->error('bibtex file does not exist:' . $this->bibtexUrl);
        } else if (strlen($bibtexContent) === 0) {
            $convertedContent = 'Bibtex file is empty.';
            $this->logger->error('Error on opening URL ' . $this->bibtexUrl . ': Bibtex file is empty');
        } else {
            $this->logger->debug('Use internal bib2html');
            if ($this->isCachable()) {
                // do cache
                $identifier = $this->getCacheIdentifier($this->bibtexUrl, $sort);
                $convertedContent = $this->getFromCache($identifier);
                if (!$convertedContent) {
                    $convertedContent = $this->bibtex2Html($this->bibtexUrl, $bibtexSettings);
                    $this->setInCache($identifier, $convertedContent, $sort);
                }
            } else {
                // do not cache
                $convertedContent = $this->bibtex2Html($this->bibtexUrl, $bibtexSettings);
            }
        }

        $this->view->assign('output', $convertedContent);
        $this->view->assign('bibtexSettings', $bibtexSettings);
    }

    /**
     * Currently, only cache entries with default settings
     *
     * @return bool
     */
    protected function isCachable() : bool
    {
        if ($this->settings['allow']
            || $this->settings['deny']
            || $this->settings['template']
            || $this->settings['sort'] !==  BibtexSettings::DEFAULT_SORT) {
            return false;
        }
        return true;
    }

    protected function setInCache(string $identifier, string $content)
    {
        $tags = [];
        // 30 days
        $lifetime = 2592000;
        $this->cache->set($identifier, $content, $tags, $lifetime);
    }

    protected function getCacheIdentifier(string $url, string $sort) : string
    {
        return md5($url . '?sort=' . $sort . '&lang=' . $this->languageId);
    }

    protected function getFromCache(string $identifier)
    {

        return $this->cache->get($identifier);
    }

    protected function bibtex2Html(string $bibtexUrl, BibtexSettings $bibtexSettings) : string
    {
        $sort = $bibtexSettings->getSort();
        if ($sort === 'none') {
            $sort = '';
        }
        $sortfixed = $bibtexSettings->isSortFixed();

        // @todo currently not implemented
        $template = $this->settings['template'] ?? '';

        $pars = '';
        if ($this->settings['allow'] ?? false) {
            $pars .= ' allow=' . $this->settings['allow'];
        }
        if ($this->settings['deny'] ?? false) {
            $pars .= ' deny=' . $this->settings['deny'];
        }
        /*
        if ($this->settings['key'] ?? false) {
            $pars .= ' allow=' . $this->settings['key'];
        }
        */

        // @todo: use configurable language mapping, e.g. via TypoScript
        $languageKey = $this->languageId === 0 ? '' : '_en';
        $style = 'uniol';
        $path = Environment::getPublicPath();
        include_once($path . '/typo3conf/ext/bibtex/Resources/Private/Php/bib2html/bib2html.php');

        $tempstr = bib2html(' [bibtex file=' . $bibtexUrl . $pars . '] ', $sort, $languageKey, $sortfixed, $template, $style);

        $tempstr = str_replace("<ul>", '<ul class="geweitet">', $tempstr);
        $tempstr = str_replace('class="toggle"', 'class="bibtextoggle"', $tempstr);
        return $tempstr;
    }

    protected function getBibtexFileContent(string $bibtexUrl) : string
    {
        // check if URL / file exists
        try {
            $content = file_get_contents($bibtexUrl, false, null, 0, 10);
        } catch (\Exception $e) {
            $this->logger->error('Error on opening URL '  . $bibtexUrl . ': ' . $e->getMessage());
            $content = '';
        }
        return $content;
    }

}
