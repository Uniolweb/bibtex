<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Controller;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Uniolit\Bibtex\Bibtex2Html\Service\Bibtex2HtmlService;
use Uniolit\Bibtex\Bibtex2Html\Service\FetchContent;
use Uniolit\Bibtex\Bibtex2Html\Service\FetchContentResult;
use Uniolit\Bibtex\Cache\BibtexCache;
use Uniolit\Bibtex\Configuration\BibtexSettings;

class BtexController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var int */
    private $languageId = 0;

    protected BibtexCache $bibtexCache;

    protected FetchContent $fetchContent;

    /** @var AssetCollector */
    protected $assetCollector;

    /** @var Bibtex2HtmlService */
    protected $bibtex2HtmlService;

    public function __construct(
        AssetCollector $assetCollector,
        Bibtex2HtmlService $bibtex2HtmlService,
        BibtexCache $bibtexCache,
        FetchContent $fetchContent
    ) {
        $this->fetchContent = $fetchContent;
        $this->assetCollector = $assetCollector;
        $this->bibtex2HtmlService = $bibtex2HtmlService;
        $this->bibtexCache = $bibtexCache;
    }

    public function initializeAction()
    {
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
        $style = 'uniol_' . ($this->languageId === 0 ? 'de' : 'en');
        return BibtexSettings::initializeWithSettings($this->settings, $style);
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

        /** @var FetchContentResult $content */
        $content = $this->fetchContent->fetchContent($bibtexSettings);
        if ($content->isOk()) {
            $result = $this->bibtex2HtmlService->bibtex2Html(
                $bibtexSettings,
                $content->getData(),
                $this->languageId
            );
            $entries = $result['entries'];
            $content->setTotalNumberOfEntries((int)($result['countEntries']['total'] ?? 0));
            $content->setParseErrors($result['parseErrors'] ?? []);
        } else {
            $entries = [];
        }
        // currently caches only metadata
        $this->bibtexCache->set($bibtexSettings->getUnifiedUrl(), $content->getMetaData());

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
}
