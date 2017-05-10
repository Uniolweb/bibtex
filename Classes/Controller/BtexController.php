<?php
class Tx_Btex_Controller_BtexController extends Tx_Extbase_MVC_Controller_ActionController
{
    public function showAction()
    {
        $sOlBibtexLang = $GLOBALS['TSFE']->tmpl->setup['page.']['config.']['language'] != 'de' ? '&lang=en' : '' ;
        
        $sOlBibtexSort = isset($_GET['sort'])?$_GET['sort']:$this->settings['sort'];   
        $aOlBibtexPars = array();
        if ($this->settings['key']) $aOlBibtexPars[]='key='.$this->settings['key'];
        if ($sOlBibtexSort) $aOlBibtexPars[]='sort='.$sOlBibtexSort;
        if ($this->settings['allow']) $aOlBibtexPars[]='allow='.$this->settings['allow'];
        if ($this->settings['deny']) $aOlBibtexPars[]='deny='.$this->settings['deny'];
        if ($this->settings['template']) $aOlBibtexPars[]='template='.$this->settings['template'];
        if ($this->settings['sortfixed']) $aOlBibtexPars[]='sortfixed='.$this->settings['sortfixed'];
        $sOlBibtexPars = count($aOlBibtexPars)?'&'.implode('&',$aOlBibtexPars):'' ;
        
        //mail('volker.burggraef@uni-oldenburg.de','BibTeX-Check',$sOlBibtexPars);
        
        $olBibtexContent = file_get_contents("http://php51.uni-oldenburg.de/www/bib2html_pr/einzel.php?bibtex=".$this->settings['link'].$sOlBibtexPars.$sOlBibtexLang);
        //$olBibtexContent = file_get_contents("http://www.uni-oldenburg.de/www/bib2html_pr/einzel.php?bibtex=".$this->settings['link'].$sOlBibtexPars.$sOlBibtexLang);
        
        $uriBuilder = clone($this->uriBuilder);
        $url = $uriBuilder->buildFrontendUri();
        $olBibtexContent = str_replace('href="?sort','href="'.$url.'?sort',$olBibtexContent);
        $olBibtexContent = str_replace('<a href="?">','<a href="'.$url.'">',$olBibtexContent);
        if(!$sOlBibtexLang) $olBibtexContent = str_replace('Go to document','Dokument aufrufen',$olBibtexContent);
        
//		$this->view->assign('link', $this->settings['link']);
//		$this->view->assign('key', $this->settings['key']);
//		$this->view->assign('sort', $this->settings['sort']);
//		$this->view->assign('allow', $this->settings['allow']);
//		$this->view->assign('deny', $this->settings['deny']);
                
                $this->view->assign('output', $olBibtexContent);

        // initialize the URIBuilder
        //$uriBuilder = clone $this->uriBuilder;
        //$uriBuilder->setCreateAbsoluteUri(true);
        //$uriBuilder->setUseCacheHash(false);

		// Get parameter:
		//$view = $_GET['view'];                  // mode because $this->view is already in use.
		
		// Parameter aus Backend Konfiguration lesen:
		// $myValue = $this->settings['id'];

		// Einen link bauen:
		// $link = $uriBuilder->setArguments(array('meinParameter1' => $meinParameter1))->buildFrontendUri());
		
		// Wenn auf eine andere Seite verwiesen werden soll, muss vor dem Aufruf des Uribuilders noch folgende
		// Zeile eingefügt werden:
		// $uriBuilder->setTargetPageUid(1); // Page number im Page tree
		
        // Etwas dem Template zuweisen:
        //$this->view->assign('selectedSemester', $semester);
		
		
	}
}