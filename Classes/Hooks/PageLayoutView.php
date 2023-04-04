<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Hooks;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->layoutService = GeneralUtility::makeInstance(PageLayoutService::class);
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
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
        $url = $this->getFieldFromFlexform('settings.link');
        if (strpos($url, 't3://file?') === 0) {
            $url = $this->getUrlFromFileLink($url);
        }
        $this->layoutService->addRow('URL', $url);

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
                sprintf('Filter (%s)', $this->getLanguageService()->sL('LLL:EXT:bibtex/Resources/Private/Language/locallang_backend.xlf:settings.filterType.' . $filterType)),
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
}
