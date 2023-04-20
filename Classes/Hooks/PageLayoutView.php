<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Hooks;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolit\Bibtex\Cache\BibtexCache;
use Uniolt3\Uniollib\Service\Backend\PageLayoutService;

/**
 * Show information about plugins in page layout view
 */
class PageLayoutView
{
    /**
     * Flexform information
     *
     * @var array
     */
    public $flexformData = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var PageLayoutService
     */
    protected $layoutService;

    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    protected string $pluginType = '';

    protected ?LanguageService $languageService = null;

    private BibtexCache $cache;

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->layoutService = GeneralUtility::makeInstance(PageLayoutService::class);
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->cache = GeneralUtility::makeInstance(BibtexCache::class);
    }

    /**
     * Returns information about this extension's pi1 plugin
     *
     * @param array $params Parameters to the hook
     * @return string Information about pi1 plugin
     */
    public function getExtensionSummary(array $params): string
    {
        $this->languageService = $this->getLanguageService();
        $listType = $params['row']['list_type'];
        /** @var string $msg */
        $msg = '';
        /** @var string $icon */
        $icon = '';

        // get configuration
        $this->flexformData = GeneralUtility::xml2array($params['row']['pi_flexform']);
        $this->generateTitle();

        // url
        $typolink = $this->getFieldFromFlexform('settings.link');
        $this->setMessage($typolink);
        if (strpos($typolink, 't3://file?') === 0) {
            // todo: can be handled in BibtexSettings
            $url = $this->getUrlFromFileLink($typolink);
        } else {
            $url = $typolink;
        }
        $this->layoutService->addRow(
            'URI',
            // todo: move style for links to general css ("skin")?
            sprintf(
                '<a href="%s" target="_blank" title="URL aufrufen" style="text-decoration: underline;color: blue;">%s</a>',
                $url,
                $url
            )
        );

        // sorting
        $sorting = $this->getFieldFromFlexform('settings.sort');
        $this->layoutService->addRow(
            $this->getLanguageService()->sL('LLL:EXT:bibtex/Resources/Private/Language/locallang.xlf:sort'),
            $this->getLanguageService()->sL('LLL:EXT:bibtex/Resources/Private/Language/locallang.xlf:sort.' . $sorting)
        );

        // filter
        $filterEntries = $this->getFieldFromFlexform('settings.filterEntries');
        $filterType = $this->getFieldFromFlexform('settings.filterType');
        if ($filterType !== 'none' && $filterEntries) {
            $this->layoutService->addRow(
                sprintf(
                    'Filter (%s)',
                    $this->getLanguageString('settings.filterType.' . $filterType)
                ),
                $filterEntries
            );
        }

        // show Links
        /*
        $this->layoutService->addDocumentationLink(
            'url ...',
            'alt text ...',
            'title ...'
        );
        */

        return $this->layoutService->render();
    }

    protected function setMessage(string $typolink): void
    {
        if (strpos($typolink, 't3://file?') === 0) {
            // todo: can be handled in BibtexSettings
            $url = $this->getUrlFromFileLink($typolink);
        } else {
            $url = $typolink;
            if (!GeneralUtility::isValidUrl($url)) {
                $this->layoutService->addMessage(
                    $this->getLanguageString('error.message.fetch.5'),
                    PageLayoutService::STATUS_ERROR
                );
                return;
            }
        }

        if (!$url) {
            $this->layoutService->addMessage(
                $this->getLanguageString('error.message.fetch.5'),
                PageLayoutService::STATUS_ERROR
            );
            return;
        }

        $fetchResult = $this->cache->get($typolink);
        if (!$fetchResult) {
            $this->layoutService->addMessage(
                $this->getLanguageString('error.message.fetch.unknown_result'),
                PageLayoutService::STATUS_WARNING
            );
            return;
        }
        $lastFetchedString = sprintf(
            '(%s: %s)',
            $this->getLanguageString('last_fetched'),
            date('d.m.Y H:i', $fetchResult->getTimestamp())
        );
        if ($fetchResult->isOk()) {
            $this->layoutService->addMessage(
                $this->getLanguageString('error.message.fetch.0') . ' ' . $lastFetchedString,
                PageLayoutService::STATUS_OK
            );
            $this->layoutService->addRow('Anzahl Einträge', (string)$fetchResult->getTotalNumberOfEntries());
            $parseErrors = $fetchResult->getParseErrors();
            foreach ($parseErrors as $parseError) {
                $this->layoutService->addMessage(
                    $this->getLanguageString('error.message.parse.' . $parseError['code']),
                    PageLayoutService::STATUS_WARNING
                );
            }
        } else {
            $this->layoutService->addMessage(
                $this->getLanguageString('error.message.fetch.' . $fetchResult->getResultCode())
                    . ' ' . $lastFetchedString,
                PageLayoutService::STATUS_ERROR
            );
        }
    }

    protected function getUrlFromFileLink(string $url): string
    {
        if (!$url) {
            return '';
        }
        $matches = [];
        if (preg_match('#t3://file\\?uid=([0-9]*)#', $url, $matches) !== 1) {
            return $url;
        }
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $uid = (int)($matches[1] ?? 0);
        if (!$uid) {
            return $url;
        }
        $file = $resourceFactory->getFileObject($uid);
        if (!$file) {
            return $url;
        }
        return $file->getPublicUrl();
    }

    /**
     * Generate and set title
     */
    protected function generateTitle(): void
    {
        $title = $this->languageService->sL('LLL:EXT:bibtex/Resources/Private/Language/locallang_backend.xlf:ce-wizard.ce.bibtex.title');
        if ($title) {
            $this->layoutService->setTitle($title);
        }
    }

    /**
     * Get field value from flexform configuration,
     * including checks if flexform configuration is available
     *
     * @param string $key name of the key
     * @param string $sheet name of the sheet
     * @return string|null if nothing found, value if found
     */
    protected function getFieldFromFlexform($key, $sheet = 'sDEF')
    {
        $flexform = $this->flexformData;
        if (isset($flexform['data'])) {
            $flexform = $flexform['data'];
            if (isset($flexform[$sheet]['lDEF'][$key]['vDEF']) && gettype($flexform[$sheet]['lDEF'][$key]['vDEF']) === 'string') {
                return trim($flexform[$sheet]['lDEF'][$key]['vDEF']);
            }
        }

        return '';
    }

    /**
     * @return LanguageService
     *
     * @todo do not use $GLOBALS['LANG'] anymore
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getLanguageString(string $languageKey, string $defaultString = '', string $languageFile = 'locallang_backend.xlf'): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:bibtex/Resources/Private/Language/' . $languageFile . ':' . $languageKey
        ) ?: $defaultString;
    }
}
