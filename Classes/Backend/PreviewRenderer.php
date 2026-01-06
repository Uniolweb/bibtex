<?php

declare(strict_types=1);
namespace Uniolweb\Bibtex\Backend;

use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Uniolweb\Bibtex\Cache\BibtexCache;
use Uniolweb\Uniollib\Service\Backend\PageLayoutService;

class PreviewRenderer extends StandardContentPreviewRenderer
{
    /**
     * @todo make this configurable, support typolink format as well
     */
    protected string $documentationUrl = 'https://uol.de/typo3doku/inhalte/bibtex';

    /**
     * Flexform information
     *
     * @var array
     */
    public $flexformData = [];

    protected string $pluginType = '';
    protected PageLayoutService $layoutService;

    /**
     * @todo autowire PageLayoutService, did not work: no such service exists
     */
    public function __construct(
        protected IconFactory $iconFactory,
        protected ExtensionConfiguration $extensionConfiguration,
        protected BibtexCache $cache,
        ?PageLayoutService $layoutService = null
    ) {
        if (!$layoutService) {
            $layoutService = GeneralUtility::makeInstance(PageLayoutService::class);
        }
        $this->layoutService = $layoutService;
    }

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        return $this->getExtensionSummary($item->getRecord());
    }

    /**
     * Returns information about this extension's pi1 plugin
     *
     * @param array<string,mixed> $record Parameters to the hook
     * @return string Information about pi1 plugin
     */
    protected function getExtensionSummary(array $record): string
    {
        $this->layoutService->initialize();
        /** @var string $msg */
        $msg = '';
        /** @var string $icon */
        $icon = '';

        // get configuration
        $this->flexformData = GeneralUtility::xml2array($record['pi_flexform']);
        $this->generateTitle();

        // url
        $typolink = $this->getFieldFromFlexform('settings.link');
        $this->setMessage($typolink);
        if (str_starts_with((string)$typolink, 't3://file?')) {
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
        if ($this->documentationUrl) {
            $this->layoutService->addDocumentationLink($this->documentationUrl);
        }

        return $this->layoutService->render();
    }

    protected function setMessage(string $typolink): void
    {
        if (str_starts_with($typolink, 't3://file?')) {
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
                'info'
            );
            return;
        }
        $this->layoutService->addRow(
            $this->getLanguageString('last_fetched'),
            date('d.m.Y H:i', $fetchResult->getTimestamp())
        );
        if ($fetchResult->isOk()) {
            $this->layoutService->addMessage(
                $this->getLanguageString('error.message.fetch.0'),
                PageLayoutService::STATUS_OK
            );
            $this->layoutService->addRow('Anzahl Einträge', (string)$fetchResult->getTotalNumberOfEntries());
            $parseErrors = $fetchResult->getParseErrors();
            if ($parseErrors) {
                $errorCodes = [];
                // title: error.message.parse.title
                foreach ($parseErrors as $parseError) {
                    $code = (int)$parseError['code'];
                    $errorCodes[$code] = $code;
                    $html = $this->getLanguageString('error.message.parse.' . $code);
                    $tip = $this->getLanguageString('error.message.parse.tip.' . $code);
                    if ($tip) {
                        $html .= '<br/>' . $tip;
                    }
                    $bibtexHtml = $parseError['bibtexHTML'] ?? '';
                    if ($bibtexHtml) {
                        $html .= '<br/><code>' . $bibtexHtml . '</code>';
                    }

                    $this->layoutService->addMessage(
                        $html,
                        PageLayoutService::STATUS_WARNING
                    );
                }
                foreach ($errorCodes as $code) {
                    $url = $this->getLanguageString('error.message.parse.url.' . $code);
                    $this->layoutService->addLink(
                        $url,
                        $this->getLanguageString('error.message.parse.url.linktext.' . $code) ?: 'Info'
                    );
                }
            }
        } else {
            $message = $this->getLanguageString('error.message.fetch.' . $fetchResult->getResultCode());
            $exceptionMessage = $fetchResult->getErrorMessage();
            if ($exceptionMessage) {
                $message .= '<br/>' . $exceptionMessage;
            }

            $this->layoutService->addMessage(
                $message,
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

        $uid = (int)($matches[1]);
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
        $title = $this->getLanguageService()->sL('LLL:EXT:bibtex/Resources/Private/Language/locallang_backend.xlf:ce-wizard.ce.bibtex.title');
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
        // It is discouraged to use $GLOBALS['LANG'] this variable directly. The LanguageServiceFactory should be used instead to retrieve the LanguageService.
        //  Never depend on $GLOBALS['LANG'] in the frontend unless you know what you are doing.
        return $GLOBALS['LANG'];
    }

    protected function getLanguageString(string $languageKey, string $defaultString = '', string $languageFile = 'locallang_backend.xlf'): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:bibtex/Resources/Private/Language/' . $languageFile . ':' . $languageKey
        ) ?: $defaultString;
    }
}
