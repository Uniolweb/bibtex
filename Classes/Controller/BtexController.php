<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Controller;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Uniolit\Bibtex\Bibtex2Html\Service\Bibtex2HtmlService;
use Uniolit\Bibtex\Configuration\BibtexSettings;

class BtexController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private int $uid = 0;

    /** @var int */
    private $languageId = 0;

    private $cache;

    /** @var AssetCollector */
    protected $assetCollector;

    /** @var Bibtex2HtmlService */
    protected $bibtex2HtmlService;

    public function __construct(AssetCollector $assetCollector, Bibtex2HtmlService $bibtex2HtmlService)
    {
        $this->assetCollector = $assetCollector;
        $this->bibtex2HtmlService = $bibtex2HtmlService;
    }

    public function initializeAction()
    {
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('bibtex_bibtexcache');

        // inlude JavaScript in footer
        $this->assetCollector->addJavaScript(
            'bibtex.js',
            'EXT:bibtex/Resources/Public/Assets/JavaScript/btex.js',
            [],
            ['priority' => false]
        );
        // include CSS in footer
        $this->assetCollector->addStyleSheet(
            'bibtex.css',
            'EXT:bibtex/Resources/Public/Assets/Css/btex.css',
            [],
            ['priority' => false]
        );
    }

    protected function initializeBibtexSettings(): BibtexSettings
    {
        $contentObj = $this->configurationManager->getContentObject();
        $this->uid = (int)$contentObj->data['uid'];

        $style = 'uniol_' . ($this->languageId === 0 ? 'de' : 'en');
        /*
        return new BibtexSettings(
            $this->uid,
            $fileType,
            $url,
            $this->settings['sort'] ?? BibtexSettings::DEFAULT_SORT,
            $style,
            $this->settings['filterType'] ?? '',
            array_filter(explode(',', $this->settings['filterEntries'] ?? ''))
        );
        */
        return BibtexSettings::initializeWithSettings($this->settings, $this->uid, $style);
    }

    /**
     * @param BibtexSettings $bibtexSettings
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public function showAction(BibtexSettings $bibtexSettings = null)
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $this->languageId = (int)($context->getPropertyFromAspect('language', 'id'));

        if ($bibtexSettings === null) {
            $bibtexSettings = $this->initializeBibtexSettings();
        }

        // todo: use caching (getCacheIdentifier. setInCache)? Is this really necessary? Plugin is uncached!
        $entries = $this->bibtex2HtmlService->bibtex2Html($bibtexSettings, $this->languageId);

        $this->view->assign('entries', $entries);
        $this->view->assign('bibtexSettings', $bibtexSettings);

        // todo handle error output
        /*
        if ($lang === 'en') {
            return 'Bibtex file does not exist or it was not possible to read the content:&nbsp; ' . $bibFile;
        }
        return 'Bibtex Datei existiert nicht oder Inhalte konnten nicht gelesen werden:&nbsp; ' . $bibFile;
        */
    }

    /**
     * Currently, only cache entries with default settings
     *
     * @return bool
     *
     * @deprecated Not used, plugin is cached plugin
     */
    protected function isCachable(): bool
    {
        // @extensionScannerIgnoreLine
        if (($this->settings['allow'] ?? false)
            || ($this->settings['deny'] ?? false)
            //|| ($this->settings['template'] ?? false)
            || ($this->settings['sort'] ?? '') !==  BibtexSettings::DEFAULT_SORT) {
            return false;
        }
        return true;
    }

    /**
     * @param string $identifier
     * @param string $content
     *
     * @deprecated Not used, plugin is cached plugin
     */
    protected function setInCache(string $identifier, string $content)
    {
        $tags = [];
        // 30 days
        $lifetime = 2592000;
        $this->cache->set($identifier, $content, $tags, $lifetime);
    }

    /**
     * @param string $url
     * @param string $sort
     * @return string
     *
     * @deprecated Not used, plugin is cached plugin
     */
    protected function getCacheIdentifier(string $url, string $sort): string
    {
        return md5($url . '?sort=' . $sort . '&lang=' . $this->languageId);
    }

    /**
     * @param string $identifier
     * @return mixed
     *
     * @deprecated Not used, plugin is cached plugin
     */
    protected function getFromCache(string $identifier)
    {
        return $this->cache->get($identifier);
    }
}
