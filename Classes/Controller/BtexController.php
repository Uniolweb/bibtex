<?php

namespace Uniol\Btex\Controller;

class BtexController extends GeneralActionController
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

        $olBibtexContent = file_get_contents("http://php51.uni-oldenburg.de/www/bib2html_pr/einzel.php?bibtex=".$this->settings['link'].$sOlBibtexPars.$sOlBibtexLang);

        $uriBuilder = clone($this->uriBuilder);
        $url = $uriBuilder->buildFrontendUri();
        $olBibtexContent = str_replace('href="?sort','href="'.$url.'?sort',$olBibtexContent);
        $olBibtexContent = str_replace('<a href="?">','<a href="'.$url.'">',$olBibtexContent);
        if(!$sOlBibtexLang) $olBibtexContent = str_replace('Go to document','Dokument aufrufen',$olBibtexContent);

        $this->view->assign('output', $olBibtexContent);

 	
		
	}
}