<?php

declare(strict_types=1);
namespace Uniolit\Bibtex\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Uniolit\Bibtex\Bibtex2Html\Service\FetchContent;
use Uniolit\Bibtex\Configuration\BibtexSettings;

class BtexController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var int */
    private $languageId = 0;

    protected FetchContent $fetchContent;

    /** @var AssetCollector */
    protected $assetCollector;

    public function __construct(
        AssetCollector $assetCollector,
        FetchContent $fetchContent
    ) {
        $this->fetchContent = $fetchContent;
        $this->assetCollector = $assetCollector;
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
    public function showAction(BibtexSettings $bibtexSettings = null): ResponseInterface
    {
        $errorMessage = '';
        $entries = [];
        $context = GeneralUtility::makeInstance(Context::class);
        // todo: just use $this->language ?
        $this->languageId = (int)($context->getPropertyFromAspect('language', 'id'));

        if ($bibtexSettings === null) {
            $bibtexSettings = $this->initializeBibtexSettings();
        }

        $contentResult = $this->fetchContent->fetchContent($bibtexSettings);
        if ($contentResult->isOk()) {
            $contentResult = $this->fetchContent->fetchEntries($bibtexSettings, $contentResult, $this->languageId);
            $this->view->assign('entries', $contentResult->getParsedEntries());
        } else {
            $this->view->assign('errorMessage', LocalizationUtility::translate(
                key: 'error.msg.fe.bibtex_file_not_avaiable',
                extensionName: 'bibtex'
            ));
        }
        $this->fetchContent->writeToCache($bibtexSettings, $contentResult);
        $this->view->assign('bibtexSettings', $bibtexSettings);

        return $this->htmlResponse();
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
