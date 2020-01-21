<?php

namespace Uniol\Btex\Controller;

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BtexController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    private $logger;

    public function initializeAction()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    public function showAction()
    {
        $sOlBibtexLang = $GLOBALS['TSFE']->tmpl->setup['page.']['config.']['language'] != 'de' ? '&lang=en' : '' ;

        $this->logger->debug("sOlBibtexLang=$sOlBibtexLang");
        
        $sOlBibtexSort = isset($_GET['sort'])?$_GET['sort']:$this->settings['sort'];
        $aOlBibtexPars = array();
        if ($this->settings['key']) {
            $aOlBibtexPars[] = 'key=' . $this->settings['key'];
        }
        if ($sOlBibtexSort) {
            $aOlBibtexPars[] = 'sort=' . $sOlBibtexSort;
        }
        if ($this->settings['allow']) {
            $aOlBibtexPars[] = 'allow=' . $this->settings['allow'];
        }
        if ($this->settings['deny']) {
            $aOlBibtexPars[] = 'deny=' . $this->settings['deny'];
        }
        if ($this->settings['template']) {
            $aOlBibtexPars[] = 'template=' . $this->settings['template'];
        }
        if ($this->settings['sortfixed']) {
            $aOlBibtexPars[] = 'sortfixed=' . $this->settings['sortfixed'];
        }
        $sOlBibtexPars = count($aOlBibtexPars)?'&'.implode('&', $aOlBibtexPars):'' ;

        $bibtexUrl = $this->settings['link'];
        $olBibtexContent = '';

        // check if URL / file exists
        try {
            $content = file_get_contents($bibtexUrl, false, null, 0, 10);
        } catch (\Exception $e) {
            $this->logger->error('Error on opening URL '  . $bibtexUrl . ': ' . $e->getMessage());
            $content = false;
        }
        if ($content === false) {
            $olBibtexContent = 'Bibtex file does not exist.';
        } else if (strlen($content) === 0) {
            $olBibtexContent = 'Bibtex file is empty.';
            $this->logger->error('Error on opening URL ' . $bibtexUrl . ': Bibtex file is empty');
        } else {
            $url = "http://php51.uni-oldenburg.de/www/bib2html_pr/einzel.php?bibtex="
                . $bibtexUrl
                . $sOlBibtexPars . $sOlBibtexLang;

            $olBibtexContent = file_get_contents($url);

            $this->logger->debug("BtexController: url=$url");

            $uriBuilder = clone($this->uriBuilder);
            $url = $uriBuilder->buildFrontendUri();
            $olBibtexContent = str_replace('href="?sort', 'href="' . $url . '?sort', $olBibtexContent);
            $olBibtexContent = str_replace('<a href="?">', '<a href="' . $url . '">', $olBibtexContent);
            if (!$sOlBibtexLang) {
                $olBibtexContent = str_replace(
                    'Go to document',
                    'Dokument aufrufen',
                    $olBibtexContent
                );
            }
        }

        $this->view->assign('output', $olBibtexContent);
    }
}
